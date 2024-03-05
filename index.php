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
 * Display information about all the mod_spromonitor modules in the requested course.
 *
 * @package     mod_spromonitor
 * @copyright   2024 onwards kordan <stringapiccola@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include necessary files and initialize parameters.
require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Get the course ID from the URL parameter.
$id = required_param('id', PARAM_INT);

// Retrieve course information from the database.
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

// Create a context for the course.
$coursecontext = context_course::instance($course->id);

// Trigger an event for viewing the course module instance list.
$eventdata = ['context' => $coursecontext];
$event = \mod_spromonitor\event\course_module_instance_list_viewed::create($eventdata);
$event->add_record_snapshot('course', $course);
$event->trigger();

// Set up the Moodle page with necessary information.
$PAGE->set_url('/mod/spromonitor/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

// Output the page header.
echo $OUTPUT->header();

// Display the heading for the module instances.
$modulenameplural = get_string('modulenameplural', 'mod_spromonitor');
echo $OUTPUT->heading($modulenameplural);

// Get all instances of mod_spromonitor in the course.
$spromonitors = get_all_instances_in_course('spromonitor', $course);

// Display a notice if there are no instances.
if (empty($spromonitors)) {
    notice(get_string('no$spromonitorinstances', 'mod_spromonitor'), new moodle_url('/course/view.php', ['id' => $course->id]));
}

// Create an HTML table to display module instances.
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

// Set table headers based on the course format.
if ($course->format == 'weeks') {
    $table->head  = [get_string('week'), get_string('name')];
    $table->align = ['center', 'left'];
} else if ($course->format == 'topics') {
    $table->head  = [get_string('topic'), get_string('name')];
    $table->align = ['center', 'left', 'left', 'left'];
} else {
    $table->head  = [get_string('name')];
    $table->align = ['left', 'left', 'left'];
}

// Populate the table with module instances.
foreach ($spromonitors as $spromonitor) {
    // Create a link to the module instance.
    if (!$spromonitor->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/spromonitor/view.php', ['id' => $spromonitor->coursemodule]),
            format_string($spromonitor->name, true),
            ['class' => 'dimmed']
        );
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/spromonitor/view.php', ['id' => $spromonitor->coursemodule]),
            format_string($spromonitor->name, true)
        );
    }

    // Add data to the table based on the course format.
    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = [$spromonitor->section, $link];
    } else {
        $table->data[] = [$link];
    }
}

// Output the HTML table.
echo html_writer::table($table);

// Output the page footer.
echo $OUTPUT->footer();
