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

namespace mod_wall\local\persistent;

use core\persistent;

/**
 * Comment persistent - threaded comments on a wall instance
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment extends persistent {
    /** @var string Current table. */
    const TABLE = 'wall_comment';

    /**
     * Return the custom definition of the properties of this model.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'wallid' => [
                'type' => PARAM_INT,
            ],
            'userid' => [
                'type' => PARAM_INT,
            ],
            'parentid' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'content' => [
                'type' => PARAM_RAW,
            ],
            'contentformat' => [
                'type' => PARAM_INT,
                'default' => FORMAT_MOODLE,
            ],
        ];
    }

    /**
     * Check if the current user can delete this comment.
     *
     * @param \context_module $context The module context.
     * @return bool
     */
    public function can_delete(\context_module $context): bool {
        global $USER;

        // Users can delete their own comments.
        if ($this->get('userid') == $USER->id) {
            return true;
        }

        // Users with deletecomment capability can delete any.
        return has_capability('mod/wall:deletecomment', $context);
    }

    /**
     * Get replies to this comment.
     *
     * @return comment[]
     */
    public function get_replies(): array {
        return self::get_records(['parentid' => $this->get('id')], 'timecreated', 'ASC');
    }

    /**
     * Check if this is a top-level comment.
     *
     * @return bool
     */
    public function is_top_level(): bool {
        return $this->get('parentid') == 0;
    }

    /**
     * Get the author's full name.
     *
     * @return string
     */
    public function get_author_name(): string {
        global $DB;
        $user = $DB->get_record('user', ['id' => $this->get('userid')]);
        return fullname($user);
    }
}
