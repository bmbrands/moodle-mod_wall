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

namespace mod_wall\local;

use core\message\message;
use moodle_url;

/**
 * Notifications helper class for mod_wall.
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications {
    /**
     * Send notification to a user when someone replies to their comment.
     *
     * @param int $parentuserid The user ID of the parent comment author.
     * @param int $replyuserid The user ID of the person who replied.
     * @param int $cmid The course module ID.
     * @param string $wallname The wall instance name.
     * @return bool True if notification sent successfully.
     */
    public static function send_reply_notification(
        int $parentuserid,
        int $replyuserid,
        int $cmid,
        string $wallname
    ): bool {
        global $DB;

        // Don't notify yourself.
        if ($parentuserid === $replyuserid) {
            return false;
        }

        $parentuser = $DB->get_record('user', ['id' => $parentuserid], '*', MUST_EXIST);
        $replyuser = $DB->get_record('user', ['id' => $replyuserid], '*', MUST_EXIST);

        $cm = get_coursemodule_from_id('wall', $cmid, 0, false, MUST_EXIST);

        // Build the link to the wall.
        $linkurl = new moodle_url('/mod/wall/view.php', ['id' => $cmid]);

        // Prepare the message data.
        $messagedata = new \stdClass();
        $messagedata->replyuser = fullname($replyuser);
        $messagedata->wallname = $wallname;
        $messagedata->link = $linkurl->out(false);

        $messagehtml = get_string('notification_reply_html', 'mod_wall', $messagedata);

        $message = new message();
        $message->component = 'mod_wall';
        $message->name = 'comment_reply';
        $message->userfrom = $replyuser;
        $message->userto = $parentuser;
        $message->subject = get_string('notification_reply_subject', 'mod_wall', $messagedata);
        $message->fullmessage = html_to_text($messagehtml);
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $messagehtml;
        $message->smallmessage = get_string('notification_reply_small', 'mod_wall', $messagedata);
        $message->notification = 1;
        $message->contexturl = $linkurl->out(false);
        $message->contexturlname = $wallname;
        $message->courseid = $cm->course;

        return message_send($message) !== false;
    }
}
