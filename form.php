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
 * Generate Chart.js charts.
 *
 * @package    tool_monitoring
 * @copyright  2023 Davide Mirra <davide.mirra@iss.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Include necessary Moodle files.
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

// Check user access or require_course_login(), require_admin(), depending on the requirements..
require_login();

// Ensure the script is only accessed within Moodle.
defined('MOODLE_INTERNAL') || die();

// Instantiate the utility class.
$utility = new \tool_monitoring\utility();

// Get the course ID and 'sproid' parameter.
$courseid = $utility->getcourseid();
$sproid = optional_param('sproid', 0, PARAM_INT);

// Set up Moodle context, page title, and other page settings.
$context = \context_course::instance($courseid);
$pagetitle = get_string('pagetitle', 'tool_monitoring');

$paramsurl['courseid'] = $courseid;
$paramsurl['sproid'] = $sproid;
$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/monitoring/form.php', $paramsurl);
$PAGE->set_pagelayout('standard');

// Include necessary JavaScript resources.
$PAGE->requires->js_call_amd(
    'core/first',
    'require',
    ['charts/chartjs/Chart.min', 'Chart'],
    ['exports' => 'Chart'],
    true
);

// Output the HTML header.
echo $OUTPUT->header();

echo \html_writer::tag('h2', $pagetitle, ['class' => 'centerpara']);

// Get localized strings for display.
$titleformspro = get_string('titleformspro', 'tool_monitoring');
$titleformparams = get_string('titleformparams', 'tool_monitoring');
$buttonsubmit = get_string('buttonsubmit', 'tool_monitoring');
$errorquestion = get_string('errorquestion', 'tool_monitoring');
$errorspro = get_string('errorspro', 'tool_monitoring');
$selectoptions = get_string('selectoptions', 'tool_monitoring');

// Initialize variables.
$data = [];
$transformedsurveysname = [];

// Get the referring URL and current URL.
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$currenturl = $_SERVER['REQUEST_URI'];

// Check if the current URL contains '/moodle/mod/lti/'.
if (strpos($referrer, '/moodle/mod/lti/') !== false) {
    // Set the session variable.
    $_SESSION['urltool'] = $referrer;
    $referrer = $_SESSION['urltool'];
}

// If 'sproid' is 0, update the current URL from the session.
if ($sproid == 0) {
    // Check if the 'urltool' key exists before using it.
    $currenturl = $_SESSION['urltool'] ?? '';
}

// Check if the current URL contains '/moodle/mod/lti/'.
if (strpos($currenturl, '/moodle/mod/lti/') !== false) {

    // Retrieve survey names.
    $surveysname = $DB->get_records('surveypro', ['course' => $courseid]);

    // Check if there are results.
    if ($surveysname) {
        // Iterate over the results and add to the associative array.
        foreach ($surveysname as $result) {
            $transformedsurveysname[] = [
                'id' => $result->id,
                'name' => $result->name,
                // Add other fields if necessary.
            ];
        }
    }

    // Prepare data for rendering the survey template.
    $data = [
        'courseid' => $courseid,
        'namesurveys' => $transformedsurveysname,
        'buttonsubmit' => $buttonsubmit,
        'titleformspro' => $titleformspro,
        'errorspro' => $errorspro,
        'selectoptions' => $selectoptions,
    ];
    $utility->rendermustachefile('templates/templatesurveys.mustache', $data);
} else {
    // Retrieve questions.
    $questions = $DB->get_records('surveypro_item', ['surveyproid' => $sproid]);

    // Transform the $questions array to match the expected structure.
    $transformedquestions = [];

    foreach ($questions as $question) {
        // Assuming $question->plugin is available and represents the question type.
        $isnumeric = ($question->plugin === 'numeric');
        $fielddetails = $DB->get_record('surveyprofield_numeric', ['itemid' => $question->id]);

        if ($question->plugin === 'numeric') {
            // If it is of type numeric, retrieve details from the surveyprofield_numeric table.
            $questioncontent = $fielddetails->variable;
            if (!isset($questioncontent)) {
                $questioncontent = $fielddetails->content;
            }

            $transformedquestions[] = [
                'id' => $question->id,
                'questioncontent' => $questioncontent, // Adjust accordingly.
                'isnumeric' => $isnumeric,
            ];
        }
    }

    // Data is now in the expected structure for the Mustache template.

    // Prepare data for rendering the question template.
    $data = [
        'questions' => $transformedquestions,
        'courseid' => $courseid,
        'sproid' => $sproid,
        'buttonsubmit' => $buttonsubmit,
        'titleformparams' => $titleformparams,
        'errorquestion' => $errorquestion,
    ];
    $utility->rendermustachefile('templates/templateparams.mustache', $data);
}

// Output the HTML footer.
echo $OUTPUT->footer();
