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
use core_external\external_value;
use mod_wall\local\api\comments;

/**
 * Web service: Delete a comment
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_comment extends external_api {
    /**
     * Delete a comment.
     *
     * @param int $cmid The course module ID.
     * @param int $commentid The comment ID.
     * @return bool
     */
    public static function execute(int $cmid, int $commentid): bool {
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'commentid' => $commentid,
        ]);

        $cm = get_coursemodule_from_id('wall', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        return comments::delete_comment($params['commentid'], $context);
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'commentid' => new external_value(PARAM_INT, 'Comment ID'),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success');
    }
}
