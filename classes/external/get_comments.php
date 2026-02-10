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

namespace mod_wall\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_wall\local\api\comments;

/**
 * Web service: Get comment thread for a wall
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_comments extends external_api {
    /**
     * Get comments for a wall.
     *
     * @param int $cmid The course module ID.
     * @return array
     */
    public static function execute(int $cmid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
        ]);

        $cm = get_coursemodule_from_id('wall', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/wall:view', $context);

        $wall = $DB->get_record('wall', ['id' => $cm->instance], '*', MUST_EXIST);
        $enablevoting = !empty($wall->enablevoting);

        $commentlist = comments::get_comments($cm->instance, $context, $enablevoting);

        return self::export_comments($commentlist);
    }

    /**
     * Convert comment objects to WS-safe arrays.
     *
     * @param array $commentlist Array of comment stdClass objects.
     * @return array
     */
    private static function export_comments(array $commentlist): array {
        $result = [];
        foreach ($commentlist as $comment) {
            $replies = [];
            if (!empty($comment->replies)) {
                $replies = self::export_comments($comment->replies);
            }
            $item = [
                'id' => $comment->id,
                'wallid' => $comment->wallid,
                'userid' => $comment->userid,
                'parentid' => $comment->parentid,
                'content' => $comment->content,
                'fullname' => $comment->fullname,
                'mention' => $comment->mention ?? '',
                'timeago' => $comment->timeago,
                'candelete' => $comment->candelete,
                'enablevoting' => $comment->enablevoting ?? false,
                'upvotes' => $comment->upvotes ?? 0,
                'downvotes' => $comment->downvotes ?? 0,
                'score' => $comment->score ?? 0,
                'uservote' => $comment->uservote ?? 0,
                'replies' => $replies,
            ];
            $result[] = $item;
        }
        return $result;
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    /**
     * Define the reply structure for WS return.
     *
     * @return external_single_structure
     */
    private static function comment_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Comment ID'),
            'wallid' => new external_value(PARAM_INT, 'Wall instance ID'),
            'userid' => new external_value(PARAM_INT, 'Author user ID'),
            'parentid' => new external_value(PARAM_INT, 'Parent comment ID'),
            'content' => new external_value(PARAM_RAW, 'Comment content'),
            'fullname' => new external_value(PARAM_TEXT, 'Author full name'),
            'mention' => new external_value(PARAM_TEXT, '@mention prefix', VALUE_OPTIONAL),
            'timeago' => new external_value(PARAM_TEXT, 'Time ago string'),
            'candelete' => new external_value(PARAM_BOOL, 'Can delete'),
            'enablevoting' => new external_value(PARAM_BOOL, 'Voting enabled', VALUE_OPTIONAL),
            'upvotes' => new external_value(PARAM_INT, 'Total upvotes', VALUE_OPTIONAL),
            'downvotes' => new external_value(PARAM_INT, 'Total downvotes', VALUE_OPTIONAL),
            'score' => new external_value(PARAM_INT, 'Net score', VALUE_OPTIONAL),
            'uservote' => new external_value(PARAM_INT, 'Current user vote', VALUE_OPTIONAL),
            'replies' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Comment ID'),
                    'wallid' => new external_value(PARAM_INT, 'Wall instance ID'),
                    'userid' => new external_value(PARAM_INT, 'Author user ID'),
                    'parentid' => new external_value(PARAM_INT, 'Parent comment ID'),
                    'content' => new external_value(PARAM_RAW, 'Comment content'),
                    'fullname' => new external_value(PARAM_TEXT, 'Author full name'),
                    'mention' => new external_value(PARAM_TEXT, '@mention prefix', VALUE_OPTIONAL),
                    'timeago' => new external_value(PARAM_TEXT, 'Time ago string'),
                    'candelete' => new external_value(PARAM_BOOL, 'Can delete'),
                    'enablevoting' => new external_value(PARAM_BOOL, 'Voting enabled', VALUE_OPTIONAL),
                    'upvotes' => new external_value(PARAM_INT, 'Total upvotes', VALUE_OPTIONAL),
                    'downvotes' => new external_value(PARAM_INT, 'Total downvotes', VALUE_OPTIONAL),
                    'score' => new external_value(PARAM_INT, 'Net score', VALUE_OPTIONAL),
                    'uservote' => new external_value(PARAM_INT, 'Current user vote', VALUE_OPTIONAL),
                    'replies' => new external_multiple_structure(
                        new external_single_structure([]),
                        'Empty at this level',
                        VALUE_OPTIONAL
                    ),
                ]),
                'Flat replies (max 1 level)',
                VALUE_OPTIONAL
            ),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(self::comment_structure());
    }
}
