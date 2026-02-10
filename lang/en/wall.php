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

/**
 * English language pack for Wall
 *
 * @package    mod_wall
 * @category   string
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Wall';
$string['modulenameplural'] = 'Walls';
$string['pluginadministration'] = 'Wall administration';
$string['pluginname'] = 'Wall';
$string['privacy:metadata'] = 'The Wall plugin stores comments posted by users.';
$string['privacy:metadata:wall_comment'] = 'Comments posted on a wall.';
$string['privacy:metadata:wall_comment:content'] = 'The content of the comment.';
$string['privacy:metadata:wall_comment:timecreated'] = 'The time the comment was created.';
$string['privacy:metadata:wall_comment:userid'] = 'The ID of the user who posted the comment.';
$string['wall:addinstance'] = 'Add a new Wall';
$string['wall:comment'] = 'Add comments to the wall';
$string['wall:deletecomment'] = 'Delete any comment';
$string['wall:view'] = 'View Wall';

// UI strings.
$string['addcomment'] = 'Add comment';
$string['cancel'] = 'Cancel';
$string['commentby'] = 'Comment by {$a}';
$string['comments'] = 'Comments';
$string['confirmdeletecomment'] = 'Are you sure you want to delete this comment?';
$string['deletecomment'] = 'Delete comment';
$string['downvote'] = 'Downvote';
$string['enablevoting'] = 'Enable voting';
$string['enablevotinghelp'] = 'Allow users to upvote and downvote comments';
$string['hidereplies'] = 'Hide replies';
$string['invalidparentcomment'] = 'Invalid parent comment';
$string['nocomments'] = 'No comments yet. Be the first to comment!';
$string['oncoursepage'] = 'Show wall on course page';
$string['oncoursepagehelp'] = 'When enabled, the comment wall will be shown directly on the course page';
$string['reply'] = 'Reply';
$string['upvote'] = 'Upvote';
$string['viewreplies'] = 'View {$a} replies';
$string['votingnotenabled'] = 'Voting is not enabled for this wall';

// Events.
$string['eventcommentcreated'] = 'Comment created';
$string['eventcommentdeleted'] = 'Comment deleted';

// Notifications.
$string['messageprovider:comment_reply'] = 'Someone replied to your comment';
$string['notification_reply_html'] = '<p>{$a->replyuser} replied to your comment on <a href="{$a->link}">{$a->wallname}</a>.</p>';
$string['notification_reply_small'] = '{$a->replyuser} replied to your comment on {$a->wallname}';
$string['notification_reply_subject'] = '{$a->replyuser} replied to your comment';
