<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_spromonitor.
 *
 * @package     mod_spromonitor
 * @copyright   2024 onwards kordan <stringapiccola@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_spromonitor\viewmanager;

// Include necessary files and initialize parameters
require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir.'/tablelib.php');

// Get optional parameters from the URL
$id = optional_param('id', 0, PARAM_INT); // Course module id.
$n = optional_param('n', 0, PARAM_INT); // Nim instance id.

// Check if $id is provided
if ($id) {
    // Retrieve the course module and course information
    $cm = get_coursemodule_from_id('spromonitor', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $spromonitor = $DB->get_record('spromonitor', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    // If $id is not provided, retrieve information based on $n
    $spromonitor = $DB->get_record('spromonitor', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $spromonitor->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('spromonitor', $spromonitor->id, $course->id, false, MUST_EXIST);
}

// Ensure user is logged in with required permissions
require_login($course, true, $cm);

// Create a context for the module
$context = context_module::instance($cm->id);

// Create an instance of the view manager
$viewman = new viewmanager($cm, $context, $spromonitor);

// Set up the Moodle page with necessary information
$paramurl['id'] = $cm->id;
$PAGE->set_url('/mod/spromonitor/view.php', $paramurl);
$PAGE->set_title(format_string($spromonitor->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output the page header
echo $OUTPUT->header();

// Check if the surveyproid is empty or fieldscsv is 'error' to determine display type
if (empty($spromonitor->surveyproid) || ($spromonitor->fieldscsv == 'error')) {
    $viewman->display_activityneedssetup();
} else {
    $viewman->display_chart();
}

// Output the page footer
echo $OUTPUT->footer();
