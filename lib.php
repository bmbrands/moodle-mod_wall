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
 * Callback implementations for Wall
 *
 * Documentation: {@link https://moodledev.io/docs/apis/plugintypes/mod}
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * List of features supported in module
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function wall_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Add Wall instance
 *
 * Given an object containing all the necessary data, (defined by the form in mod_form.php)
 * this function will create a new instance and return the id of the instance
 *
 * @param stdClass $moduleinstance form data
 * @param mod_wall_mod_form $form the form
 * @return int new instance id
 */
function wall_add_instance($moduleinstance, $form = null) {
    global $DB;

    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = time();

    $id = $DB->insert_record('wall', $moduleinstance);

    // Save media file.
    $context = context_module::instance($moduleinstance->coursemodule);
    if (isset($moduleinstance->media)) {
        file_save_draft_area_files(
            $moduleinstance->media,
            $context->id,
            'mod_wall',
            'media',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }

    $completiontimeexpected = !empty($moduleinstance->completionexpected) ? $moduleinstance->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $moduleinstance->coursemodule,
        'wall',
        $id,
        $completiontimeexpected
    );
    return $id;
}

/**
 * Updates an instance of the Wall in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param stdClass $moduleinstance An object from the form in mod_form.php
 * @param mod_wall_mod_form $form The form
 * @return bool True if successful, false otherwis
 */
function wall_update_instance($moduleinstance, $form = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    $DB->update_record('wall', $moduleinstance);

    // Save media file.
    $context = context_module::instance($moduleinstance->coursemodule);
    if (isset($moduleinstance->media)) {
        file_save_draft_area_files(
            $moduleinstance->media,
            $context->id,
            'mod_wall',
            'media',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }

    $completiontimeexpected = !empty($moduleinstance->completionexpected) ? $moduleinstance->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $moduleinstance->coursemodule,
        'wall',
        $moduleinstance->id,
        $completiontimeexpected
    );

    return true;
}

/**
 * Removes an instance of the Wall from the database.
 *
 * @param int $id Id of the module instance
 * @return bool True if successful, false otherwise
 */
function wall_delete_instance($id) {
    global $DB;

    $record = $DB->get_record('wall', ['id' => $id]);
    if (!$record) {
        return false;
    }

    // Delete all votes and comments for this wall.
    $commentids = $DB->get_fieldset_select('wall_comment', 'id', 'wallid = :wallid', ['wallid' => $id]);
    if (!empty($commentids)) {
        [$insql, $inparams] = $DB->get_in_or_equal($commentids);
        $DB->delete_records_select('wall_vote', "commentid $insql", $inparams);
    }
    $DB->delete_records('wall_comment', ['wallid' => $id]);

    // Delete all instance-level votes.
    $DB->delete_records('wall_instance_vote', ['wallid' => $id]);

    // Delete all calendar events.
    $events = $DB->get_records('event', ['modulename' => 'wall', 'instance' => $record->id]);
    foreach ($events as $event) {
        calendar_event::load($event)->delete();
    }

    // Delete the instance.
    $DB->delete_records('wall', ['id' => $id]);

    return true;
}

/**
 * Shows the wall comment thread on the course page.
 *
 * @param cm_info $cm course module info.
 */
function wall_cm_info_view(cm_info $cm) {
    global $DB, $PAGE;

    $conf = $DB->get_record('wall', ['id' => $cm->instance], '*', MUST_EXIST);

    if (empty($conf->oncoursepage)) {
        return;
    }

    $context = context_module::instance($cm->id);
    $cancomment = has_capability('mod/wall:comment', $context);
    $enablevoting = !empty($conf->enablevoting);

    // Get aggregate comment count for the compact course page bar.
    $stats = \mod_wall\local\api\comments::get_wall_stats($conf->id);

    // Get the wall instance vote data for the current user.
    $wallvotedata = wall_get_instance_vote_data($conf->id);

    // Build template context for the compact bar.
    $templatecontext = [
        'cmid' => $cm->id,
        'wallid' => $conf->id,
        'enablevoting' => $enablevoting,
        'wallscore' => $wallvotedata['score'],
        'walluservote' => $wallvotedata['uservote'],
        'walluserupvoted' => ($wallvotedata['uservote'] === 1),
        'walluserdownvoted' => ($wallvotedata['uservote'] === -1),
        'commentcount' => $stats->commentcount,
    ];

    // Add media if available.
    $media = wall_get_media_url($context->id);
    if ($media) {
        $templatecontext['mediaurl'] = $media['url'];
        $templatecontext['mediaimage'] = $media['isimage'];
        $templatecontext['mediavideo'] = $media['isvideo'];
        $templatecontext['hasmedia'] = true;
    }

    // Initialize JS module with full config.
    $PAGE->requires->js_call_amd('mod_wall/wall', 'init', [
        $cm->id, $conf->id, $cancomment, $enablevoting, true,
    ]);

    $output = $PAGE->get_renderer('core');
    $content = $output->render_from_template('mod_wall/course_page', $templatecontext);
    $cm->set_content($content, true);
}

/**
 * Get the wall instance vote data (independent from comment votes).
 *
 * @param int $wallid The wall instance ID.
 * @return array Associative array with upvotes, downvotes, score, uservote.
 */
function wall_get_instance_vote_data(int $wallid): array {
    global $DB, $USER;

    $upvotes = $DB->count_records_select(
        'wall_instance_vote',
        'wallid = :wallid AND vote = 1',
        ['wallid' => $wallid]
    );
    $downvotes = $DB->count_records_select(
        'wall_instance_vote',
        'wallid = :wallid AND vote = -1',
        ['wallid' => $wallid]
    );
    $uservote = $DB->get_field('wall_instance_vote', 'vote', [
        'wallid' => $wallid,
        'userid' => $USER->id,
    ]);

    return [
        'upvotes' => (int)$upvotes,
        'downvotes' => (int)$downvotes,
        'score' => (int)$upvotes - (int)$downvotes,
        'uservote' => $uservote ? (int)$uservote : 0,
    ];
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 */
function mod_wall_check_updates_since(cm_info $cm, $from, $filter = []) {
    $updates = course_check_module_updates_since($cm, $from, ['content'], $filter);
    return $updates;
}

/**
 * Serve the files from the mod_wall file areas.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function wall_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if ($filearea !== 'media') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_wall', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Get the media URL for a wall instance.
 *
 * @param int $contextid The context ID.
 * @return array|null Array with 'url' and 'isimage'/'isvideo' keys, or null.
 */
function wall_get_media_url(int $contextid): ?array {
    $fs = get_file_storage();
    $files = $fs->get_area_files($contextid, 'mod_wall', 'media', 0, 'sortorder', false);
    $file = reset($files);
    if (!$file) {
        return null;
    }

    $url = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename()
    );

    $mimetype = $file->get_mimetype();
    return [
        'url' => $url->out(false),
        'isimage' => (strpos($mimetype, 'image/') === 0),
        'isvideo' => (strpos($mimetype, 'video/') === 0),
    ];
}
