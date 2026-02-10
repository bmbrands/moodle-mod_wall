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
 * Provides all the settings and steps to perform one complete backup of the activity
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_wall_activity_structure_step extends backup_activity_structure_step {
    /**
     * Backup structure
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element representing a DB table.
        $wall = new backup_nested_element(
            'wall',
            ['id'],
            ['name', 'intro', 'introformat', 'oncoursepage', 'enablevoting', 'timemodified']
        );

        $comments = new backup_nested_element('comments');
        $comment = new backup_nested_element(
            'comment',
            ['id'],
            ['wallid', 'userid', 'parentid', 'content', 'contentformat',
             'timecreated', 'timemodified', 'usermodified']
        );

        $votes = new backup_nested_element('votes');
        $vote = new backup_nested_element(
            'vote',
            ['id'],
            ['commentid', 'userid', 'vote', 'timecreated', 'timemodified']
        );

        $instancevotes = new backup_nested_element('instancevotes');
        $instancevote = new backup_nested_element(
            'instancevote',
            ['id'],
            ['wallid', 'userid', 'vote', 'timecreated', 'timemodified']
        );

        // Build the tree: wall -> comments -> comment -> votes -> vote.
        // wall -> instancevotes -> instancevote.
        $wall->add_child($comments);
        $comments->add_child($comment);

        $comment->add_child($votes);
        $votes->add_child($vote);

        $wall->add_child($instancevotes);
        $instancevotes->add_child($instancevote);

        // Define sources.
        $wall->set_source_table('wall', ['id' => backup::VAR_ACTIVITYID]);

        // User data sources - only include if userinfo is requested.
        if ($userinfo) {
            $comment->set_source_table('wall_comment', ['wallid' => backup::VAR_PARENTID], 'id ASC');
            $vote->set_source_table('wall_vote', ['commentid' => backup::VAR_PARENTID], 'id ASC');
            $instancevote->set_source_table('wall_instance_vote', ['wallid' => backup::VAR_ACTIVITYID], 'id ASC');
        }

        // Define id annotations.
        $comment->annotate_ids('user', 'userid');
        $comment->annotate_ids('user', 'usermodified');
        $vote->annotate_ids('user', 'userid');
        $instancevote->annotate_ids('user', 'userid');

        // Define file annotations.
        $wall->annotate_files('mod_wall', 'intro', null);

        // Return the root element (wall), wrapped into standard activity structure.
        return $this->prepare_activity_structure($wall);
    }
}
