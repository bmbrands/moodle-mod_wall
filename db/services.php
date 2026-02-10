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
 * Web service external functions and service definitions.
 *
 * @package    mod_wall
 * @category   external
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_wall_get_comments' => [
        'classname'     => 'mod_wall\external\get_comments',
        'methodname'    => 'execute',
        'description'   => 'Get comment thread for a wall',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/wall:view',
    ],
    'mod_wall_add_comment' => [
        'classname'     => 'mod_wall\external\add_comment',
        'methodname'    => 'execute',
        'description'   => 'Add a comment to a wall',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/wall:comment',
    ],
    'mod_wall_delete_comment' => [
        'classname'     => 'mod_wall\external\delete_comment',
        'methodname'    => 'execute',
        'description'   => 'Delete a comment from a wall',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/wall:deletecomment',
    ],
    'mod_wall_vote_comment' => [
        'classname'     => 'mod_wall\external\vote_comment',
        'methodname'    => 'execute',
        'description'   => 'Upvote or downvote a wall comment',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/wall:comment',
    ],
    'mod_wall_vote_wall' => [
        'classname'     => 'mod_wall\external\vote_wall',
        'methodname'    => 'execute',
        'description'   => 'Upvote or downvote a wall instance',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/wall:view',
    ],
];
