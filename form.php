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

use tool_monitoring\Utility;

require_once __DIR__ . '/../../../config.php';

defined('MOODLE_INTERNAL') || die();

// Instantiate the Utility class
$utility = new Utility();

// Get the course ID and 'sproid' parameter
$courseid = $utility->getCourseId();
$sproid = optional_param('sproid', 0, PARAM_INT);

// Set up Moodle context, page title, and other page settings
$context = \context_course::instance($courseid);
$pagetitle = get_string('pagetitle', 'tool_monitoring');

$paramsurl['courseid'] = $courseid;
$paramsurl['sproid'] = $sproid;
$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/monitoring/form.php', $paramsurl);
$PAGE->set_pagelayout('standard');

// Include necessary JavaScript resources
$PAGE->requires->js_call_amd(
    'core/first',
    'require',
    array('charts/chartjs/Chart.min', 'Chart'),
    array('exports' => 'Chart'),
    true
);

// Output the HTML header
echo $OUTPUT->header();

echo "<h2 align='center'>".$pagetitle."</h2>";

// Get localized strings for display
$titleformspro = get_string('titleformspro', 'tool_monitoring');
$titleformparams = get_string('titleformparams', 'tool_monitoring');
$buttonsubmit = get_string('buttonsubmit', 'tool_monitoring');
$errorquestion = get_string('errorquestion', 'tool_monitoring');
$errorspro = get_string('errorspro', 'tool_monitoring');
$selectoptions = get_string('selectoptions', 'tool_monitoring');

// Initialize variables
$data = [];
$transformedSurveysName = [];

// Get the referring URL and current URL
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$currentUrl = $_SERVER['REQUEST_URI'];

// Check if the current URL contains '/moodle/mod/lti/'
if (strpos($referrer, '/moodle/mod/lti/') !== false) {
    // Set the session variable
    $_SESSION['urltool'] = $referrer;
    $referrer = $_SESSION['urltool'];
}

// If 'sproid' is 0, update the current URL from the session
if ($sproid == 0) {
    $currentUrl = $_SESSION['urltool'];
}

// Check if the current URL contains '/moodle/mod/lti/'
if (strpos($currentUrl, '/moodle/mod/lti/') !== false) {

    // Retrieve survey names
    $surveysname = $DB->get_records('surveypro', array('course' => $courseid));

    // Check if there are results
    if ($surveysname) {
        // Iterate over the results and add to the associative array
        foreach ($surveysname as $result) {
            $transformedSurveysName[] = [
                'id' => $result->id,
                'name' => $result->name,
                // Add other fields if necessary
            ];
        }
    }

    // Prepare data for rendering the survey template
    $data = [
        'courseid' => $courseid,
        'namesurveys' => $transformedSurveysName,
        'buttonsubmit' => $buttonsubmit,
        'titleformspro' => $titleformspro,
        'errorspro' => $errorspro,
        'selectoptions' => $selectoptions
    ];
    $utility->rendermustachefile('templates/templatesurveys.mustache', $data);

} else {
    // Retrieve questions
    $questions = $DB->get_records('surveypro_item', array('surveyproid' => $sproid));

    // Transform the $questions array to match the expected structure
    $transformedQuestions = [];
    
    foreach ($questions as $question) {
        // Assuming $question->plugin is available and represents the question type
        $isNumeric = ($question->plugin === 'numeric');
        $fieldDetails = $DB->get_record('surveyprofield_numeric', array('itemid' => $question->id));
    
        if ($question->plugin === 'numeric') {
            // If it is of type numeric, retrieve details from the surveyprofield_numeric table
            $questionContent = $fieldDetails->variable;
            if(!isset($questionContent)){
                $questionContent = $fieldDetails->content;
            }
    
            $transformedQuestions[] = [
                'id' => $question->id,
                'questionContent' => $questionContent, // Adjust accordingly
                'isNumeric' => $isNumeric,
            ];
        }
    }

    // Data is now in the expected structure for the Mustache template

    // Prepare data for rendering the question template
    $data = [
        'questions' => $transformedQuestions,
        'courseid' => $courseid,
        'sproid' => $sproid,
        'buttonsubmit' => $buttonsubmit,
        'titleformparams' => $titleformparams,
        'errorquestion' => $errorquestion
    ];
    $utility->rendermustachefile('templates/templateparams.mustache', $data);
}

// Output the HTML footer
echo $OUTPUT->footer();
