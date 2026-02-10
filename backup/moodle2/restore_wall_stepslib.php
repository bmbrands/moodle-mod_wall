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
 * Structure step to restore one Wall activity
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_wall_activity_structure_step extends restore_activity_structure_step {
    /**
     * Structure step to restore one wall activity
     *
     * @return array
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $paths = [];
        $paths[] = new restore_path_element('wall', '/activity/wall');

        if ($userinfo) {
            $paths[] = new restore_path_element('wall_comment', '/activity/wall/comments/comment');
            $paths[] = new restore_path_element('wall_vote', '/activity/wall/comments/comment/votes/vote');
            $paths[] = new restore_path_element('wall_instance_vote', '/activity/wall/instancevotes/instancevote');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a wall restore.
     *
     * @param array $data
     * @return void
     */
    protected function process_wall($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Insert the wall record.
        $newitemid = $DB->insert_record('wall', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process a comment restore.
     *
     * @param array $data
     * @return void
     */
    protected function process_wall_comment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->wallid = $this->get_new_parentid('wall');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);

        // Remap parentid if it references another comment.
        if (!empty($data->parentid)) {
            $data->parentid = $this->get_mappingid('wall_comment', $data->parentid);
        }

        $newitemid = $DB->insert_record('wall_comment', $data);
        $this->set_mapping('wall_comment', $oldid, $newitemid);
    }

    /**
     * Process a comment vote restore.
     *
     * @param array $data
     * @return void
     */
    protected function process_wall_vote($data) {
        global $DB;

        $data = (object)$data;

        $data->commentid = $this->get_new_parentid('wall_comment');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('wall_vote', $data);
    }

    /**
     * Process a wall instance vote restore.
     *
     * @param array $data
     * @return void
     */
    protected function process_wall_instance_vote($data) {
        global $DB;

        $data = (object)$data;

        $data->wallid = $this->get_new_parentid('wall');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('wall_instance_vote', $data);
    }

    /**
     * Actions to be executed after the restore is completed.
     */
    protected function after_execute() {
        // Add wall related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_wall', 'intro', null);
    }
}
