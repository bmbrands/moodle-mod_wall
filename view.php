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
 * View Wall instance
 *
 * @package    mod_wall
 * @copyright  2026 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_wall\local\api\comments;

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$w = optional_param('w', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('wall', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('wall', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('wall', ['id' => $w], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('wall', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

\mod_wall\event\course_module_viewed::create_from_record($moduleinstance, $cm, $course)->trigger();

$PAGE->set_url('/mod/wall/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));

// Check capabilities.
$cancomment = has_capability('mod/wall:comment', $context);
$enablevoting = !empty($moduleinstance->enablevoting);

// Get existing comments.
$commentlist = comments::get_comments($moduleinstance->id, $context, $enablevoting);

// Enrich comments with wallid and cancomment for template rendering.
foreach ($commentlist as $comment) {
    $comment->wallid = $moduleinstance->id;
    $comment->cancomment = $cancomment;
}

// Build template context.
$templatecontext = [
    'cmid' => $cm->id,
    'wallid' => $moduleinstance->id,
    'cancomment' => $cancomment,
    'enablevoting' => $enablevoting,
    'comments' => $commentlist,
    'hascomments' => !empty($commentlist),
    'commentcount' => count($commentlist),
];

// Initialize JS module.
$PAGE->requires->js_call_amd('mod_wall/wall', 'init', [
    $cm->id, $moduleinstance->id, $cancomment, $enablevoting, false,
]);

echo $OUTPUT->header();

// Show intro if available.
if (!empty($moduleinstance->intro)) {
    echo $OUTPUT->box(format_module_intro('wall', $moduleinstance, $cm->id), 'generalbox mod_introbox', 'wallintro');
}

echo $OUTPUT->render_from_template('mod_wall/view_page', $templatecontext);

echo $OUTPUT->footer();
