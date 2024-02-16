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

// Ensure script is accessed within Moodle.
defined('MOODLE_INTERNAL') || die();

// Instantiate the utility class.
$utility = new \tool_monitoring\utility();

// Retrieve parameters safely.
$username = optional_param('username', null, PARAM_TEXT);
$singlecsv = optional_param('singlecsv', null, PARAM_TEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$sproid = optional_param('sproid', 0, PARAM_INT);
$selectedfieldsvalue = optional_param('selectedfields', '', PARAM_TEXT);
$selecteddatavalue = optional_param('selecteddata', '', PARAM_TEXT);
$createrow = optional_param('createrow', 0, PARAM_INT);
$updaterow = optional_param('updaterow', 0, PARAM_INT);

/*
echo "Username: " . $username . "<br>";
echo "Single CSV: " . $singlecsv . "<br>";
echo "Course ID: " . $courseid . "<br>";
echo "Spro ID: " . $sproid . "<br>";
echo "Selected Fields Value: " . $selectedfieldsvalue . "<br>";
echo "Selected Data Value: " . $selecteddatavalue . "<br>";
echo "Create Row: " . $createrow . "<br>";
echo "Update Row: " . $updaterow . "<br>";

die;*/

$row = $DB->get_record('tool_monitoring', ['surveyproid' => $sproid], '*');
$tooldata = new stdClass();

if ($createrow == 1) {
    echo "aaaaaaaa";
    // If the record doesn't exist, add a new record to tool_monitoring.
    $newRecord = new stdClass();
    $newRecord->courseid = $courseid;
    $newRecord->surveyproid = $sproid;
    $newRecord->fieldscsv = $selectedfieldsvalue;
    $newRecord->measuredateid = $selecteddatavalue;
    $newRecord->timecreated = time();
    $tooldata = $DB->insert_record('tool_monitoring', $newRecord);
} elseif ($updaterow == 1){
    echo "asdsdsasd";
    $row->fieldscsv = $selectedfieldsvalue;
    $row->measuredateid = $selecteddatavalue;
    $row->timemodified = time();
    $tooldata = $DB->update_record('tool_monitoring', $row);
}
else{
    $tooldata = $row;
}

print_r($tooldata); die;

// Set or update the session variable directly.
$_SESSION['selectedfields'] = $selectedfieldsvalue;

// Use the session variable.
$selectedfields = $_SESSION['selectedfields'];

$selectedfieldsarray = [];
if (isset($selectedfields)) {
    $selectedfieldsarray = $utility->handleselectedfields($selectedfields);
}

$variablesarray = [];
foreach ($selectedfieldsarray as $field) {
    // Execute the query to get the variables associated with known itemids.
    $sql = "SELECT variable FROM {surveyprofield_numeric} WHERE itemid = :field";
    $params = ['field' => $field];
    $result = $DB->get_record_sql($sql, $params);
    // Check if there are results before accessing the variable property.
    if (!empty($result) && isset($result->variable)) {
        // Add the variable value to the end of the array.
        array_push($variablesarray, $result->variable);
    } else {
        // If there are no results, add an empty value to the array (or a default value, depending on your needs).
        $variablesarray[] = null; // You can change this to the desired default value.
    }
}

$context = \context_course::instance($courseid);

if (!isset($username)) {
    $username = $singlecsv;
}

$pagetitle = get_string('pagetitle', 'tool_monitoring');

$paramsurl['courseid'] = $courseid;
$paramsurl['sproid'] = $sproid;
$paramsurl['selectedfields'] = $selectedfields;

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/monitoring/index.php', $paramsurl);
$PAGE->set_pagelayout('standard');
$PAGE->requires->js_call_amd(
    'core/first',
    'require',
    ['charts/chartjs/Chart.min', 'Chart'],
    ['exports' => 'Chart'],
    true
);

echo $OUTPUT->header();

// URL of the course dashboard.
$paramurl['id'] = $courseid;
$paramurl['section'] = 0;
$urldashboard = new moodle_url('/course/view.php', $paramurl);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
// Now $course contains all course information, including the name.
$coursename = $course->fullname;

$dataheader = [
    'pagetitle' => $pagetitle,
    'urldashboard' => $urldashboard,
    'courseName' => $coursename,
];

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

$filenamearray = [];

// Check user's capability to access all charts.
if (!$canaccessallcharts) {
    // If not, display a single user chart.
    $utility->singleuserchart($messageemptychart, $titlechart, $variablesarray, $selectedfieldsarray, $selecteddatavalue, null);
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

    if ($username && count($usersearched) <= 1) {
        // If a single user is found, display their chart.
        foreach ($usersearched as $user) {
            $userid = $user->id;
            $username = $user->username;
        }

        if (count($usersearched) === 1) {
            $utility->singleuserchart(
                '',
                $clinicaldata . $username,
                $variablesarray,
                $selectedfieldsarray,
                $userid
            );

            // Execute queries and prepare data for the user.
            $results = $utility->executequeries($selectedfieldsarray, $selecteddatavalue, $userid);

            foreach ($results as $result) {
                $prepareddata = $utility->preparearray($result);

                if (!empty($prepareddata['content']) && !empty($prepareddata['timecreated'])) {
                    $chartdataarrays[] = $prepareddata;
                }
            }

            // Generate a filename and add it to the array.
            $mergedarray = $utility->createmergedarray($variablesarray, $chartdataarrays);

            $filename = $utility->generatefilename($username, $datestring, $variablesarray, $mergedarray);
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

            // Execute queries and prepare data for the user.
            $results = $utility->executequeries($selectedfieldsarray, $selecteddatavalue, $userid);

            $chartdataarrays = [];
            foreach ($results as $result) {
                $prepareddata = $utility->preparearray($result);

                if (!empty($prepareddata['content']) && !empty($prepareddata['timecreated'])) {
                    $chartdataarrays[] = $prepareddata;
                }
            }

            // If the participant hasn't completed the surveypro, skip.
            if (!empty($chartdataarrays)) {
                // Display the chart for the user.
                $title = $clinicaldata . $username;
                echo \html_writer::tag(
                    'div class="padding-top-bottom"',
                    $utility->generatechart(
                        $variablesarray,
                        $chartdataarrays,
                        $title,
                    )
                );

                // Generate a filename and add it to the array.
                $mergedarray = $utility->createmergedarray($variablesarray, $chartdataarrays);
                $filename = $utility->generatefilename($username, $datestring, $variablesarray, $mergedarray);
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
