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

// Check user access or require_course_login(), require_admin(), depending on the requirements..
require_login();

// Ensure the script is accessed within Moodle.
defined('MOODLE_INTERNAL') || die();

// Instantiate the utility class.
$utility = new \tool_monitoring\utility();

//$bck = $utility->bckTable();

// Retrieve parameters safely.
$username = optional_param('username', null, PARAM_TEXT);
$singlecsv = optional_param('singlecsv', null, PARAM_TEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$sproid = optional_param('sproid', 0, PARAM_INT);
$selectedfieldsid = optional_param('selectedfields', '', PARAM_TEXT);
$selecteddateid = optional_param('selecteddate', 0, PARAM_INT);
$createrow = optional_param('createrow', 0, PARAM_INT);
$updaterow = optional_param('updaterow', 0, PARAM_INT);

// Retrieve the tool monitoring record based on the surveyproid.
$toolrow = $DB->get_record('tool_monitoring', ['surveyproid' => $sproid], '*');

// If the "createrow" flag is set, create a new record in tool_monitoring.
if ($createrow == 1) {
    // If the record doesn't exist, add a new record to tool_monitoring.
    $newRecord = new stdClass();
    $newRecord->courseid = $courseid;
    $newRecord->surveyproid = $sproid;
    $newRecord->fieldscsv = $selectedfieldsid;
    $newRecord->measuredateid = $selecteddateid;
    $newRecord->timecreated = time();
    $DB->insert_record('tool_monitoring', $newRecord);
} elseif ($updaterow == 1) {
    // If the "updaterow" flag is set, update the existing tool_monitoring record.
    $toolrow->fieldscsv = $selectedfieldsid;
    $toolrow->measuredateid = $selecteddateid;
    $toolrow->timemodified = time();
    $DB->update_record('tool_monitoring', $toolrow);
}

// Extract selected fields and date ID from the tool monitoring record.
$selectedfields = $toolrow->fieldscsv;
$selecteddate = $toolrow->measuredateid;

$selectedfieldsarray = [];
$variablesarray = [];

// Handle selected fields and extract variables.
if (isset($selectedfields)) {
    $selectedfieldsarray = $utility->handleselectedfields($selectedfields);
}

foreach ($selectedfieldsarray as $field) {
    // Execute the query to get the variables associated with known itemids.
    $sqlparams = "SELECT variable FROM {surveyprofield_numeric} WHERE itemid = :field";
    $params = ['field' => $field];
    $result = $DB->get_record_sql($sqlparams, $params);

    // Check if there are results before accessing the variable property.
    if (!empty($result) && isset($result->variable)) {
        // Add the variable value to the end of the array.
        array_push($variablesarray, $result->variable);
    } else {
        // If there are no results, add an empty value to the array (or a default value, depending on your needs).
        $variablesarray[] = null; // You can change this to the desired default value.
    }
}


// Execute SQL query to get the variable associated with the selected date.
$sqldata = "SELECT variable FROM {surveyprofield_date} WHERE itemid = :field";
$fieldate = ['field' => $selecteddate];
$result = $DB->get_record_sql($sqldata, $fieldate);
$dateselectedvariable = $result->variable;

// Set up the Moodle context.
$context = \context_course::instance($courseid);

// If the username is not set, use the singlecsv parameter.
if (!isset($username)) {
    $username = $singlecsv;
}

// Get the page title.
$pagetitle = get_string('pagetitle', 'tool_monitoring');

// Set up URL parameters for the page.
$paramsurl['courseid'] = $courseid;
$paramsurl['sproid'] = $sproid;
$paramsurl['selectedfields'] = $selectedfields;

// Set up the Moodle page.
$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/monitoring/index.php', $paramsurl);
$PAGE->set_pagelayout('standard');

// Include necessary JavaScript for Chart.js.
$PAGE->requires->js_call_amd(
    'core/first',
    'require',
    ['charts/chartjs/Chart.min', 'Chart'],
    ['exports' => 'Chart'],
    true
);

// Output the header.
echo $OUTPUT->header();

// Set up URL for the course dashboard.
$paramurl['id'] = $courseid;
$paramurl['section'] = 0;
$urldashboard = new moodle_url('/course/view.php', $paramurl);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$coursename = $course->fullname;

// Data for the header template.
$dataheader = [
    'pagetitle' => $pagetitle,
    'urldashboard' => $urldashboard,
    'courseName' => $coursename,
];

// Render the header template.
$utility->rendermustachefile('templates/templateheader.mustache', $dataheader);

// SQL queries for users and user count.
$sqlusers = 'SELECT DISTINCT(u.id), u.username
               FROM {role_assignments} ra
                   JOIN {user} u on u.id = ra.userid
                   JOIN {context} ctx ON ctx.id = ra.contextid
              WHERE ctx.instanceid = :courseid';
$idcourse = ['courseid' => $courseid];
$queryusers = $DB->get_records_sql($sqlusers, $idcourse);

$querycountusers = 'SELECT count(*) as num
                      FROM {role_assignments} ra
                          JOIN {user} u on u.id = ra.userid
                          JOIN {context} ctx ON ctx.id = ra.contextid
                     WHERE ctx.instanceid = :courseid';
$idcourse = ['courseid' => $courseid];
$resultcountuser = $DB->get_record_sql($querycountusers, $idcourse);
$numuser = $resultcountuser->num;

// Other variables and strings.
$calendarparam['view'] = 'month';
$calendarurl = new moodle_url('/calendar/view.php', $calendarparam);
$datedefault = get_string('date', 'tool_monitoring');
$clinicaldata = get_string('clinicaldata', 'tool_monitoring');
$insusername = get_string('insusername', 'tool_monitoring');
$searchusername = get_string('searchusername', 'tool_monitoring');
$search = get_string('search', 'tool_monitoring');
$titlechart = get_string('titlechart', 'tool_monitoring');
$messageemptychart = get_string('messageemptychart', 'tool_monitoring');
$gocalendar = get_string('gocalendar', 'tool_monitoring');
$csvgen = get_string('csvgen', 'tool_monitoring');
$goback = get_string('goback', 'tool_monitoring');

// Check user's capability to access all charts.
$canaccessallcharts = has_capability('tool/monitoring:accessallcharts', $context);

$datevariable = '';

// Check if the date variable is set, otherwise use the default.
if (isset($dateselectedvariable)) {
    $datevariable = $dateselectedvariable;
} else {
    $datevariable = $datedefault;
}

$filenamearray = [];

// Check user's capability to access all charts.
if (!$canaccessallcharts) {
    // If not, display a single user chart.
    $utility->singleuserchart(
        $messageemptychart,
        $titlechart,
        $variablesarray,
        $selectedfieldsarray,
        $selecteddataid,
        null
    );
} else {
    // Prepare data for the search bar template.
    $data = [
        'searchusername' => $searchusername,
        'formAction' => '',
        'insusername' => $insusername,
        'search' => $search,
        'courseid' => $courseid,
        'selectedfields' => $selectedfields,
    ];

    // Render the search bar template.
    $utility->rendermustachefile('templates/templatesearchbar.mustache', $data);

    // SQL query to search for users by username.
    $sqlusers = 'SELECT DISTINCT(u.id), u.username
            FROM {role_assignments} ra
                JOIN {user} u on u.id = ra.userid
                WHERE u.username LIKE :username';
    $paramsquery = ['username' => '%' . $username . '%'];
    $usersearched = $DB->get_records_sql($sqlusers, $paramsquery);

    if ($username && count($usersearched) === 0) {
        // If a single user is found, display their chart.
        foreach ($usersearched as $user) {
            $userid = $user->id;
            $username = $user->username;
        }
        
        if (isset($usersearched) && count($usersearched) === 1) {
            $utility->singleuserchart(
                '',
                $clinicaldata . $username,
                $variablesarray,
                $selectedfieldsarray,
                $selecteddataid,
                $userid
            );

            // Execute queries and prepare data for the user.
            $chartdataarray = $utility->executequeries($selectedfieldsarray, $selecteddataid, $userid);

            $transformedarray = $utility->transformarray($chartdataarray);

            // Generate a filename and add it to the array.
            $mergedarray = $utility->createmergedarray($variablesarray, $transformedarray);

            $filename = $utility->generatefilename($username, $datevariable, $variablesarray, $mergedarray);
            array_push($filenamearray, $filename);

            // Prepare data for the CSV template.
            $data = [
                'filenamearray' => $filenamearray,
                'singlecsv' => $username,
                'csvgen' => $csvgen,
                'courseid' => $courseid,
            ];

            // Render the CSV template.
            $utility->rendermustachefile('templates/templatecsv.mustache', $data);
        } else if (count($usersearched) === 0) {
            // If no user is found, display a message.
            $messagenotfound = get_string('messagenotfound', 'tool_monitoring');
            echo \html_writer::tag('div class="padding-top-bottom"', '<h5>' . $messagenotfound . $username . '</h5>');
        }
    } else {
        // If multiple users are found, display charts for each.
        if ($usersearched) {
            $queryusers = $usersearched;
        }

        foreach ($queryusers as $user) {
            $username = $user->username;
            $userid = $user->id;

            $chartdataarray = $utility->executequeries($selectedfieldsarray, $selecteddataid, $userid);

            $transformedarray = $utility->transformarray($chartdataarray);

            // If the participant hasn't completed the surveypro, skip.
            if (!empty($chartdataarray)) {
                // Display the chart for the user.
                $title = $clinicaldata . $username;
                echo \html_writer::tag(
                    'div class="padding-top-bottom"',
                    $utility->generatechart(
                        $variablesarray,
                        $transformedarray,
                        $title,
                    )
                );

                // Generate a filename and add it to the array.
                $mergedarray = $utility->createmergedarray($variablesarray, $transformedarray);
                $filename = $utility->generatefilename($username, $datevariable, $variablesarray, $mergedarray);
                array_push($filenamearray, $filename);
            }
        }

        // Prepare data for the CSV template.
        $data = [
            'filenamearray' => $filenamearray,
            'csvgen' => $csvgen,
            'courseid' => $courseid,
        ];

        // Render the CSV template.
        $utility->rendermustachefile('templates/templatecsv.mustache', $data);
    }
}

// Prepare data for the calendar and back button template.
$data = [
    'calendarurl' => $calendarurl,
    'gocalendar' => $gocalendar,
    'goback' => $goback,
];

// Redirect to calendar.
$utility->rendermustachefile('templates/templatecalendarandbb.mustache', $data);

// Output footer.
echo $OUTPUT->footer();
