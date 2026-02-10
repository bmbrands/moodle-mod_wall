<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_wall\local\api;

use mod_wall\local\persistent\comment;
use mod_wall\local\notifications;
use moodle_exception;
use context_module;
use stdClass;

/**
 * Comments API - CRUD for threaded comments on a wall
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comments {
    /**
     * Get all comments for a wall instance (threaded, max 2 levels).
     *
     * @param int $wallid The wall instance ID.
     * @param context_module $context The module context.
     * @param bool $enablevoting Whether voting is enabled for this wall.
     * @return array Array of comment data objects with nested replies.
     */
    public static function get_comments(int $wallid, context_module $context, bool $enablevoting = false): array {
        // Get all top-level comments (newest first for 9gag-style).
        $toplevel = comment::get_records([
            'wallid' => $wallid,
            'parentid' => 0,
        ], 'timecreated', 'DESC');

        $result = [];
        foreach ($toplevel as $commentrecord) {
            $result[] = self::export_comment($commentrecord, $context, $enablevoting);
        }

        // Sort by score when voting is enabled, otherwise newest first.
        if ($enablevoting) {
            usort($result, function ($a, $b) {
                $scorediff = ($b->score ?? 0) - ($a->score ?? 0);
                if ($scorediff !== 0) {
                    return $scorediff;
                }
                return $b->timecreated - $a->timecreated;
            });
        }

        return $result;
    }

    /**
     * Get aggregate statistics for a wall (total comments and votes).
     *
     * @param int $wallid The wall instance ID.
     * @return stdClass Object with commentcount, upvotes, downvotes, totalscore.
     */
    public static function get_wall_stats(int $wallid): stdClass {
        global $DB;

        $commentcount = $DB->count_records('wall_comment', ['wallid' => $wallid]);

        // Get total upvotes and downvotes across all comments in this wall.
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN v.vote = 1 THEN 1 ELSE 0 END), 0) AS upvotes,
                    COALESCE(SUM(CASE WHEN v.vote = -1 THEN 1 ELSE 0 END), 0) AS downvotes
                FROM {wall_vote} v
                JOIN {wall_comment} c ON c.id = v.commentid
                WHERE c.wallid = :wallid";
        $votes = $DB->get_record_sql($sql, ['wallid' => $wallid]);

        return (object)[
            'commentcount' => (int)$commentcount,
            'upvotes' => (int)($votes->upvotes ?? 0),
            'downvotes' => (int)($votes->downvotes ?? 0),
            'totalscore' => (int)($votes->upvotes ?? 0) - (int)($votes->downvotes ?? 0),
        ];
    }

    /**
     * Export a comment with its replies for template rendering.
     * Replies are flattened to max depth 2 (top-level + 1 reply level).
     *
     * @param comment $commentrecord The comment persistent.
     * @param context_module $context The module context.
     * @param bool $enablevoting Whether voting is enabled.
     * @return stdClass
     */
    public static function export_comment(
        comment $commentrecord,
        context_module $context,
        bool $enablevoting = false
    ): stdClass {
        global $DB, $OUTPUT, $USER;

        $user = $DB->get_record('user', ['id' => $commentrecord->get('userid')]);

        $data = (object)[
            'id' => $commentrecord->get('id'),
            'wallid' => $commentrecord->get('wallid'),
            'userid' => $commentrecord->get('userid'),
            'parentid' => $commentrecord->get('parentid'),
            'content' => format_text(
                $commentrecord->get('content'),
                $commentrecord->get('contentformat'),
                ['context' => $context]
            ),
            'fullname' => fullname($user),
            'userpictureurl' => $OUTPUT->user_picture(
                $user,
                ['size' => 35, 'link' => false, 'includetoken' => true]
            ),
            'timecreated' => $commentrecord->get('timecreated'),
            'timecreatedformatted' => userdate($commentrecord->get('timecreated')),
            'timeago' => self::time_ago($commentrecord->get('timecreated')),
            'candelete' => $commentrecord->can_delete($context),
            'istoplevel' => $commentrecord->is_top_level(),
            'replies' => [],
            'enablevoting' => $enablevoting,
        ];

        // Add vote data if voting is enabled.
        if ($enablevoting) {
            $upvotes = $DB->count_records_select(
                'wall_vote',
                'commentid = :commentid AND vote = 1',
                ['commentid' => $commentrecord->get('id')]
            );
            $downvotes = $DB->count_records_select(
                'wall_vote',
                'commentid = :commentid AND vote = -1',
                ['commentid' => $commentrecord->get('id')]
            );
            $uservote = $DB->get_field('wall_vote', 'vote', [
                'commentid' => $commentrecord->get('id'),
                'userid' => $USER->id,
            ]);
            $data->upvotes = (int)$upvotes;
            $data->downvotes = (int)$downvotes;
            $data->score = (int)$upvotes - (int)$downvotes;
            $data->uservote = $uservote ? (int)$uservote : 0;
            $data->userupvoted = ($data->uservote === 1);
            $data->userdownvoted = ($data->uservote === -1);
        }

        // Get replies for top-level comments - flattened to one level.
        if ($commentrecord->is_top_level()) {
            $data->replies = self::get_flat_replies($commentrecord, $context, $enablevoting);
            $data->hasreplies = !empty($data->replies);
            $data->replycount = count($data->replies);
        }

        return $data;
    }

    /**
     * Get all replies for a top-level comment, flattened to a single level.
     * Deep replies (replies to replies) are collected at the same level with @mention prefix.
     *
     * @param comment $parentcomment The top-level parent comment.
     * @param context_module $context The module context.
     * @param bool $enablevoting Whether voting is enabled.
     * @return array Flat array of reply data objects.
     */
    private static function get_flat_replies(
        comment $parentcomment,
        context_module $context,
        bool $enablevoting = false
    ): array {
        global $DB;

        // Get ALL descendants of this top-level comment.
        $allreplies = [];
        self::collect_all_replies($parentcomment->get('id'), $allreplies);

        // Sort by timecreated ASC.
        usort($allreplies, function ($a, $b) {
            return $a->get('timecreated') - $b->get('timecreated');
        });

        $result = [];
        foreach ($allreplies as $reply) {
            $exported = self::export_reply($reply, $context, $enablevoting);
            $result[] = $exported;
        }

        // Sort by score when voting is enabled, then newest first.
        if ($enablevoting) {
            usort($result, function ($a, $b) {
                $scorediff = ($b->score ?? 0) - ($a->score ?? 0);
                if ($scorediff !== 0) {
                    return $scorediff;
                }
                return $b->timecreated - $a->timecreated;
            });
        }

        return $result;
    }

    /**
     * Recursively collect all replies (descendants) of a comment.
     *
     * @param int $parentid The parent comment ID.
     * @param array $allreplies Accumulated replies (passed by reference).
     */
    private static function collect_all_replies(int $parentid, array &$allreplies): void {
        $directreplies = comment::get_records(['parentid' => $parentid], 'timecreated', 'ASC');
        foreach ($directreplies as $reply) {
            $allreplies[] = $reply;
            // Recurse to get deeper replies.
            self::collect_all_replies($reply->get('id'), $allreplies);
        }
    }

    /**
     * Export a reply comment (level 2) with @mention for the parent author.
     *
     * @param comment $commentrecord The reply comment persistent.
     * @param context_module $context The module context.
     * @param bool $enablevoting Whether voting is enabled.
     * @return stdClass
     */
    private static function export_reply(
        comment $commentrecord,
        context_module $context,
        bool $enablevoting = false
    ): stdClass {
        global $DB, $OUTPUT, $USER;

        $user = $DB->get_record('user', ['id' => $commentrecord->get('userid')]);

        // Get the parent comment's author name for @mention.
        $parentuser = null;
        $mention = '';
        $parentid = $commentrecord->get('parentid');
        if ($parentid > 0) {
            $parentcomment = new comment($parentid);
            $parentuserrecord = $DB->get_record('user', ['id' => $parentcomment->get('userid')]);
            if ($parentuserrecord) {
                $parentuser = $parentuserrecord;
                $mention = '@' . fullname($parentuserrecord);
            }
        }

        $data = (object)[
            'id' => $commentrecord->get('id'),
            'wallid' => $commentrecord->get('wallid'),
            'userid' => $commentrecord->get('userid'),
            'parentid' => $commentrecord->get('parentid'),
            'content' => format_text(
                $commentrecord->get('content'),
                $commentrecord->get('contentformat'),
                ['context' => $context]
            ),
            'fullname' => fullname($user),
            'mention' => $mention,
            'userpictureurl' => $OUTPUT->user_picture(
                $user,
                ['size' => 35, 'link' => false, 'includetoken' => true]
            ),
            'timecreated' => $commentrecord->get('timecreated'),
            'timecreatedformatted' => userdate($commentrecord->get('timecreated')),
            'timeago' => self::time_ago($commentrecord->get('timecreated')),
            'candelete' => $commentrecord->can_delete($context),
            'istoplevel' => false,
            'enablevoting' => $enablevoting,
        ];

        // Add vote data if voting is enabled.
        if ($enablevoting) {
            $upvotes = $DB->count_records_select(
                'wall_vote',
                'commentid = :commentid AND vote = 1',
                ['commentid' => $commentrecord->get('id')]
            );
            $downvotes = $DB->count_records_select(
                'wall_vote',
                'commentid = :commentid AND vote = -1',
                ['commentid' => $commentrecord->get('id')]
            );
            $uservote = $DB->get_field('wall_vote', 'vote', [
                'commentid' => $commentrecord->get('id'),
                'userid' => $USER->id,
            ]);
            $data->upvotes = (int)$upvotes;
            $data->downvotes = (int)$downvotes;
            $data->score = (int)$upvotes - (int)$downvotes;
            $data->uservote = $uservote ? (int)$uservote : 0;
            $data->userupvoted = ($data->uservote === 1);
            $data->userdownvoted = ($data->uservote === -1);
        }

        return $data;
    }

    /**
     * Add a comment to a wall.
     * If replying to a level-2 reply, the parentid is adjusted to the top-level comment
     * so that the reply stays at level 2 (flattened). The @mention is added automatically.
     *
     * @param int $wallid The wall instance ID.
     * @param string $content The comment content.
     * @param int $parentid The parent comment ID (0 for top-level).
     * @param context_module $context The module context.
     * @param int $cmid The course module ID (for notifications).
     * @return comment The created comment.
     */
    public static function add_comment(
        int $wallid,
        string $content,
        int $parentid,
        context_module $context,
        int $cmid = 0
    ): comment {
        global $USER, $DB;

        require_capability('mod/wall:comment', $context);

        $replytouser = null;
        $actualtoplevelid = 0;

        // If replying, verify parent exists and belongs to same wall.
        if ($parentid > 0) {
            $parent = new comment($parentid);
            if ($parent->get('wallid') != $wallid) {
                throw new moodle_exception('invalidparentcomment', 'mod_wall');
            }
            $replytouser = $parent->get('userid');

            // Determine the actual top-level parent for 2-level flattening.
            if ($parent->is_top_level()) {
                // Replying to a top-level comment: parent stays as is.
                $actualtoplevelid = $parentid;
            } else {
                // Replying to a reply (level 2+): flatten to the top-level parent.
                // The stored parentid becomes the original parentid (the comment we're replying to)
                // so we can show the @mention, but it's stored under the top-level comment.
                $actualtoplevelid = $parent->get('parentid');
                // If the parent's parent is also not top-level, walk up.
                $grandparent = new comment($actualtoplevelid);
                while (!$grandparent->is_top_level()) {
                    $actualtoplevelid = $grandparent->get('parentid');
                    $grandparent = new comment($actualtoplevelid);
                }
                // Keep the parentid as the comment being replied to (for @mention).
                // The flattening happens at display time.
            }
        }

        // Create the comment. The parentid is kept as the actual reply target for @mention resolution.
        $commentrecord = new comment(0, (object)[
            'wallid' => $wallid,
            'userid' => $USER->id,
            'parentid' => $parentid,
            'content' => $content,
            'contentformat' => FORMAT_HTML,
        ]);
        $commentrecord->create();

        // Trigger event.
        \mod_wall\event\comment_created::create([
            'objectid' => $commentrecord->get('id'),
            'context' => $context,
            'relateduserid' => $USER->id,
        ])->trigger();

        // Send notification to the person being replied to.
        if ($replytouser && $cmid > 0) {
            $wall = $DB->get_record('wall', ['id' => $wallid]);
            $wallname = $wall ? format_string($wall->name) : '';
            notifications::send_reply_notification(
                (int)$replytouser,
                (int)$USER->id,
                $cmid,
                $wallname
            );
        }

        return $commentrecord;
    }

    /**
     * Delete a comment.
     *
     * @param int $commentid The comment ID.
     * @param context_module $context The module context.
     * @return bool
     */
    public static function delete_comment(int $commentid, context_module $context): bool {
        global $DB;

        $commentrecord = new comment($commentid);

        if (!$commentrecord->can_delete($context)) {
            throw new moodle_exception('nopermission', 'error');
        }

        // Trigger event before deletion.
        \mod_wall\event\comment_deleted::create([
            'objectid' => $commentid,
            'context' => $context,
        ])->trigger();

        // Delete all descendant replies and their votes.
        $allreplies = [];
        self::collect_all_replies($commentid, $allreplies);
        foreach ($allreplies as $reply) {
            $DB->delete_records('wall_vote', ['commentid' => $reply->get('id')]);
            $reply->delete();
        }

        // Delete votes on this comment.
        $DB->delete_records('wall_vote', ['commentid' => $commentid]);

        return $commentrecord->delete();
    }

    /**
     * Delete all comments and votes for a wall instance.
     *
     * @param int $wallid The wall instance ID.
     * @return void
     */
    public static function delete_all_comments(int $wallid): void {
        global $DB;
        // Delete all votes for comments in this wall.
        $commentids = $DB->get_fieldset_select('wall_comment', 'id', 'wallid = :wallid', ['wallid' => $wallid]);
        if (!empty($commentids)) {
            [$insql, $inparams] = $DB->get_in_or_equal($commentids);
            $DB->delete_records_select('wall_vote', "commentid $insql", $inparams);
        }
        $DB->delete_records('wall_comment', ['wallid' => $wallid]);
    }

    /**
     * Count comments for a wall instance.
     *
     * @param int $wallid The wall instance ID.
     * @return int
     */
    public static function count_comments(int $wallid): int {
        return comment::count_records(['wallid' => $wallid]);
    }

    /**
     * Format a timestamp as a human-readable "time ago" string.
     *
     * @param int $timestamp The timestamp.
     * @return string
     */
    private static function time_ago(int $timestamp): string {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return get_string('now', 'core');
        }
        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return get_string('numminutes', 'core', $minutes);
        }
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return get_string('numhours', 'core', $hours);
        }
        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return get_string('numdays', 'core', $days);
        }

        return userdate($timestamp, get_string('strftimedatefullshort', 'core_langconfig'));
    }
}
