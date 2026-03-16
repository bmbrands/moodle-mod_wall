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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Form for adding and editing Wall instances
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wall_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Prepare the media file manager.
        $mediaoptions = [
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['image', 'video'],
        ];
        $context = $this->context;
        if ($context && $context->contextlevel === CONTEXT_MODULE) {
            $draftitemid = file_get_submitted_draft_itemid('media');
            file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'mod_wall',
                'media',
                0,
                $mediaoptions
            );
            $mform->setDefault('media', $draftitemid);
        }

        // General fieldset.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', empty($CFG->formatstringstriptags) ? PARAM_CLEANHTML : PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        if (!empty($this->_features->introeditor)) {
            // Description element that is usually added to the General fieldset.
            $this->standard_intro_elements();
        }

        $mform->addElement(
            'filemanager',
            'media',
            get_string('media', 'mod_wall'),
            null,
            [
                'subdirs' => 0,
                'maxbytes' => 0,
                'maxfiles' => 1,
                'accepted_types' => ['image', 'video'],
            ]
        );
        $mform->addHelpButton('media', 'media', 'mod_wall');

        $mform->addElement(
            'advcheckbox',
            'oncoursepage',
            get_string(
                'oncoursepage',
                'mod_wall'
            ),
            get_string('oncoursepagehelp', 'mod_wall'),
            ['group' => 1],
            [0, 1]
        );

        $mform->addElement(
            'advcheckbox',
            'enablevoting',
            get_string(
                'enablevoting',
                'mod_wall'
            ),
            get_string('enablevotinghelp', 'mod_wall'),
            ['group' => 1],
            [0, 1]
        );

        // Other standard elements that are displayed in their own fieldsets.
        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
