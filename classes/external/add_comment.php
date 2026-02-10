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
use core_external\external_single_structure;
use core_external\external_value;
use mod_wall\local\api\comments;

/**
 * Web service: Add a comment to a wall
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_comment extends external_api {
    /**
     * Add a comment to a wall.
     *
     * @param int $cmid The course module ID.
     * @param string $content The comment content.
     * @param int $parentid The parent comment ID (0 for top-level).
     * @return array
     */
    public static function execute(int $cmid, string $content, int $parentid = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'content' => $content,
            'parentid' => $parentid,
        ]);

        $cm = get_coursemodule_from_id('wall', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $commentrecord = comments::add_comment(
            $cm->instance,
            $params['content'],
            $params['parentid'],
            $context,
            $cm->id
        );

        $exported = comments::export_comment($commentrecord, $context);

        return [
            'id' => $exported->id,
            'content' => $exported->content,
            'fullname' => $exported->fullname,
            'timeago' => $exported->timeago,
            'candelete' => $exported->candelete,
        ];
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'content' => new external_value(PARAM_RAW, 'Comment content'),
            'parentid' => new external_value(PARAM_INT, 'Parent comment ID (0 for top-level)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Comment ID'),
            'content' => new external_value(PARAM_RAW, 'Comment content'),
            'fullname' => new external_value(PARAM_TEXT, 'Author full name'),
            'timeago' => new external_value(PARAM_TEXT, 'Time ago string'),
            'candelete' => new external_value(PARAM_BOOL, 'Can delete'),
        ]);
    }
}
