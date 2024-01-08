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

$utility = new Utility();

// If you search by username in the search bar.
$username = optional_param('username', null, PARAM_TEXT);
$singlecsv = optional_param('singlecsv', null, PARAM_TEXT);
$csv = optional_param('csv', 0, PARAM_INT);

// Get POST parameters safely
$courseid = optional_param('courseid', 0, PARAM_INT);
$sproid = optional_param('sproid', 0, PARAM_INT);
// Get the value received in the request
// Get the value from the request
$selectedFieldsValue = optional_param('selectedFields', '', PARAM_TEXT);

// Set or update the session variable directly
$_SESSION['selectedFields'] = $selectedFieldsValue;

// Use the session variable
$selectedFields = $_SESSION['selectedFields'];

$selectedFieldsArray = [];
if (isset($selectedFields)) {
    $selectedFieldsArray = $utility->handleSelectedFields($selectedFields);
}

$variablesArray = [];
foreach ($selectedFieldsArray as $field) {
    // Execute the query to get the variables associated with known itemids
    $sql = "SELECT variable FROM {surveyprofield_numeric} WHERE itemid = :field";
    $params = ['field' => $field];
    $result = $DB->get_record_sql($sql, $params);
    // Check if there are results before accessing the variable property
    if (!empty($result) && isset($result->variable)) {
        // Add the variable value to the end of the array
        array_push($variablesArray, $result->variable);
    } else {
        // If there are no results, add an empty value to the array (or a default value, depending on your needs)
        $variablesArray[] = null; // You can change this to the desired default value
    }
}

$context = \context_course::instance($courseid);

if (!isset($username)) {
    $username = $singlecsv;
}

$pagetitle = get_string('pagetitle', 'tool_monitoring');

$paramsurl['courseid'] = $courseid;
$paramsurl['sproid'] = $sproid;
$paramsurl['selectedFields'] = $selectedFields;

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/monitoring/index.php', $paramsurl);
$PAGE->set_pagelayout('standard');
$PAGE->requires->js_call_amd(
    'core/first',
    'require',
    array('charts/chartjs/Chart.min', 'Chart'),
    array('exports' => 'Chart'),
    true
);

echo $OUTPUT->header();


// URL of the course dashboard
$paramurl['id'] = $courseid;
$paramurl['section'] = 0;
$urldashboard = new moodle_url('/course/view.php', $paramurl);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
// Now $course contains all course information, including the name
$courseName = $course->fullname;


$dataHeader = array(
    'pagetitle' => $pagetitle,
    'urldashboard' => $urldashboard,
    'courseName' => $courseName
);

$utility->rendermustachefile('templates/templateheader.mustache', $dataHeader);


$sqlusers = 'SELECT u.id, u.username
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

$calendarparam['view'] = 'month';
$calendarurl = new moodle_url('/calendar/view.php', $calendarparam);

$datestring = get_string('date', 'tool_monitoring');

$clinicaldata = get_string('clinicaldata', 'tool_monitoring');
$insusername = get_string('insusername', 'tool_monitoring');
$searchusername = get_string('searchusername', 'tool_monitoring');
$search = get_string('search', 'tool_monitoring');
$titlechart = get_string('titlechart', 'tool_monitoring');
$messageemptychart = get_string('messageemptychart', 'tool_monitoring');
$gocalendar = get_string('gocalendar', 'tool_monitoring');
$csvgen = get_string('csvgen', 'tool_monitoring');
$goback = get_string('goback', 'tool_monitoring');

$canaccessallcharts = has_capability('tool/monitoring:accessallcharts', $context);

$filenamearray = array();

if (!$canaccessallcharts) {
    // Use the values in the array for your function
    $utility->singleuserchart($messageemptychart, $titlechart, $variablesArray, $selectedFieldsArray, null);
} else {
    $data = array(
        'searchusername' => $searchusername,
        'formAction' => '',
        'insusername' => $insusername,
        'search' => $search,
        'courseid' => $courseid,
        'selectedFields' => $selectedFields
    );

    // TODO pass the selected fields to the searchbar's mustache because otherwise I won't find them when I click on search

    $utility->rendermustachefile('templates/templatesearchbar.mustache', $data);

    $sqlusers = 'SELECT u.id, u.username
            FROM {role_assignments} ra
                JOIN {user} u on u.id = ra.userid
                WHERE u.username LIKE :username';
    $paramsquery = ['username' => '%' . $username . '%'];
    $usersearched = $DB->get_records_sql($sqlusers, $paramsquery);

    if ($username && count($usersearched) <= 1) {

        foreach ($usersearched as $user) {
            $userid = $user->id;
            $username = $user->username;
        }

        // After getting results from the query
        if (count($usersearched) === 1) {

            $utility->singleuserchart(
                '',
                $clinicaldata . $username,
                $variablesArray,
                $selectedFieldsArray,
                $userid
            );

            $results = $utility->executequeries($userid, $selectedFieldsArray);

            foreach ($results as $result) {
                $preparedData = $utility->preparearray($result);

                if (!empty($preparedData['content']) && !empty($preparedData['timecreated'])) {
                    $chartDataArrays[] = $preparedData;
                }
            }
            $mergedarray = $utility->createmergedarray($variablesArray);

            $filename = $utility->generateFilename($username, $csv, $datestring, $variablesArray, $mergedarray);

            array_push($filenamearray, $filename); // Add the file name to the array.

            $data = array(
                'filenamearray' => $filenamearray,
                'singlecsv' => $username,
                'csvgen' => $csvgen,
                'courseid' => $courseid,
            );

            $utility->rendermustachefile('templates/templatecsv.mustache', $data);
        } elseif (count($usersearched) === 0) {
            $messagenotfound = get_string('messagenotfound', 'tool_monitoring');
            echo \html_writer::tag('div class="padding-top-bottom"', '<h5>' .
                $messagenotfound . $username . '</h5>');
        }
    } else {
        if ($usersearched) {
            $queryusers = $usersearched;
        }

        foreach ($queryusers as $user) {

            $username = $user->username;
            $userid = $user->id;

            $results = $utility->executequeries($userid, $selectedFieldsArray);

            $chartDataArrays = [];
            foreach ($results as $result) {
                $preparedData = $utility->preparearray($result);

                if (!empty($preparedData['content']) && !empty($preparedData['timecreated'])) {
                    $chartDataArrays[] = $preparedData;
                }
            }

            // If participant into for loop haven't compiled the surveypro.
            if (!empty($chartDataArrays)) {
                $title = $clinicaldata . $username;
                echo \html_writer::tag(
                    'div class="padding-top-bottom"',
                    $utility->generatechart(
                        $title,
                        $variablesArray,
                        $chartDataArrays
                    )
                );

                $mergedarray = $utility->createmergedarray($variablesArray);

                $filename = $utility->generateFilename($username, $csv, $datestring, $variablesArray, $mergedarray);

                array_push($filenamearray, $filename); // Add the file name to the array.
            }
        }

        $data = array(
            'filenamearray' => $filenamearray,
            'csvgen' => $csvgen,
            'courseid' => $courseid,
        );

        // Download csv files.
        $utility->rendermustachefile('templates/templatecsv.mustache', $data);
    }
}

$data = array(
    'calendarurl' => $calendarurl,
    'gocalendar' => $gocalendar,
    'goback' => $goback
);
// Redirect calendar.
$utility->rendermustachefile('templates/templatecalendarandbb.mustache', $data);

echo $OUTPUT->footer();
