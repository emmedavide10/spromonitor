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
 * @copyright  2024 Davide Mirra <davide.mirra@iss.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Include necessary Moodle files.
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

// Check user access or require_course_login(), require_admin(), depending on the requirements.
require_login();

// Ensure the script is only accessed within Moodle.
defined('MOODLE_INTERNAL') || die();


// Instantiate the utility class.
$utility = new \tool_monitoring\utility();

// Get the course ID and 'sproid' parameter.
$courseid = $utility->getcourseid();
if($courseid==0){
    $courseid = optional_param('courseid', 0, PARAM_INT);
}

$sproid = optional_param('sproid', 0, PARAM_INT);
$updaterow = optional_param('updaterow', 0, PARAM_INT);
$createrow = optional_param('createrow', 0, PARAM_INT);



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
$titleformdates = get_string('titleformdates', 'tool_monitoring');
$buttoncontinue = get_string('buttoncontinue', 'tool_monitoring');
$buttonsubmit = get_string('buttonsubmit', 'tool_monitoring');
$errorquestion = get_string('errorquestion', 'tool_monitoring');
$errordata = get_string('errordata', 'tool_monitoring');
$selectoptions = get_string('selectoptions', 'tool_monitoring');
$errorspro = get_string('errorspro', 'tool_monitoring');
$defaultfields = get_string('defaultfields', 'tool_monitoring');
$customfields = get_string('customfields', 'tool_monitoring');
$errorradiobtn = get_string('errorradiobtn', 'tool_monitoring');


// Initialize variables.
$data = [];
$transformedsurveysname = [];

// Get the referring URL.
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$currenturl = '';

// Check if the current URL contains '/mod/lti/'.
if (strpos($referrer, '/mod/lti/') !== false && $sproid == 0) {
    // Set the session variable.
    $_SESSION['urltool'] = $referrer;
    $currenturl = $_SESSION['urltool'];
}

$setupfields = has_capability('tool/monitoring:setupfields', $context);
$existingRecord = $DB->get_record('tool_monitoring', ['surveyproid' => $sproid]);

//echo $setupfields; die;

// Check if the current URL contains '/mod/lti/'.
if (isset($currenturl) && is_string($currenturl) && strpos($currenturl, '/mod/lti/') !== false && $setupfields) {

    // Retrieve survey names.
    $surveysname = $DB->get_records('surveypro', ['course' => $courseid]);

    // Check if there are results.
    if ($surveysname) {
        // Iterate over the results and add to the associative array.
        foreach ($surveysname as $result) {
            // Check if the survey ID is already in tool_monitoring.
            $transformedsurveysname[] = [
                'id' => $result->id,
                'name' => $result->name,
                // Add other fields if necessary.
            ];
        }
    }
    if ($existingRecord) {
        $updaterow = 1;
    } else {
        $createrow = 1;
    }
    // Prepare data for rendering the survey template.
    $data = [
        'sproid' => $sproid,
        'courseid' => $courseid,
        'namesurveys' => $transformedsurveysname,
        'buttonsubmit' => $buttonsubmit,
        'titleformspro' => $titleformspro,
        'errorspro' => $errorspro,
        'selectoptions' => $selectoptions,
        'capability' => $setupfields,
        'updaterow' => $updaterow,
        'createrow' => $createrow,
    ];
    $utility->rendermustachefile('templates/templatesurveys.mustache', $data);
} elseif (isset($currenturl) && is_string($currenturl) && strpos($currenturl, '/mod/lti/') !== true && $setupfields) {

    $questions = $DB->get_records('surveypro_item', ['surveyproid' => $sproid]);

    $transformedquestionsnumeric = [];
    $transformedquestionsdate = [];

    foreach ($questions as $question) {
        // Assuming $question->plugin is available and represents the question type.
        $isnumeric = ($question->plugin === 'numeric');
        $isdate = ($question->plugin === 'date');

        if ($isnumeric || $isdate) {
            // If it is of type numeric or date, retrieve details accordingly.
            $fielddetails = null;
            if ($isnumeric) {
                $fielddetails = $DB->get_record('surveyprofield_numeric', ['itemid' => $question->id]);
            } elseif ($isdate) {
                $fielddetails = $DB->get_record('surveyprofield_date', ['itemid' => $question->id]);
            }

            if ($fielddetails) {
                $questioncontent = $fielddetails->variable;
                if (!isset($questioncontent)) {
                    $questioncontent = $fielddetails->content;
                }

                if ($isnumeric) {
                    $transformedquestionsnumeric[] = [
                        'id' => $question->id,
                        'questioncontent' => $questioncontent,
                        'isnumeric' => $isnumeric,
                    ];
                }

                if ($isdate) {
                    $transformedquestionsdate[] = [
                        'id' => $question->id,
                        'questioncontent' => $questioncontent,
                        'isdate' => $isdate,
                    ];
                }
            }
        }
    }

    $data = [
        'numquestions' => $transformedquestionsnumeric,
        'datequestions' => $transformedquestionsdate,
        'courseid' => $courseid,
        'sproid' => $sproid,
        'buttoncontinue' => $buttoncontinue,
        'buttonsubmit' => $buttonsubmit,
        'titleformparams' => $titleformparams,
        'titleformdates' => $titleformdates,
        'errorquestion' => $errorquestion,
        'errorspro' => $errorspro,
        'errordata' => $errordata,
        'updaterow' => $updaterow,
        'createrow' => $createrow,
    ];

    //echo $existingRecord; die;
    if ($existingRecord && $updaterow == 0 ) {
        $data = [
            'sproid' => $sproid,
            'courseid' => $courseid,
            'selectoptions' => $selectoptions,
            'customfields' => $customfields,
            'defaultfields' => $defaultfields,
            'buttoncontinue' => $buttoncontinue,
            'updaterow' => $updaterow,
            'errorradiobtn' => $errorradiobtn,
        ];
        $utility->rendermustachefile('templates/templatecheckfields.mustache', $data);
    } elseif ($existingRecord && $updaterow == 1) {
        $utility->rendermustachefile('templates/templateparams.mustache', $data);
    } elseif (!$existingRecord) {
        $utility->rendermustachefile('templates/templateparams.mustache', $data);
    }
} elseif (!$setupfields) {
    // Retrieve survey names.
    $surveysname = $DB->get_records('surveypro', ['course' => $courseid]);

    // Check if there are results.
    if ($surveysname) {
        // Iterate over the results and add to the associative array.
        foreach ($surveysname as $result) {
            // Check if the survey ID is already in tool_monitoring.
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
        'capability' => $setupfields,
    ];
    $utility->rendermustachefile('templates/templatesurveys.mustache', $data);
}

// Output the HTML footer.
echo $OUTPUT->footer();
