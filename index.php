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

use tool_monitoring\utility;

require(__DIR__ . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();

global $COURSE;

// If you search by username in the search bar.
$username = optional_param('username', null, PARAM_TEXT);
$singlecsv = optional_param('singlecsv', null, PARAM_TEXT);
$csv = optional_param('csv', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT); // Course_module id.

if (!isset($username)) {
    $username = $singlecsv;
}

$utility = new utility();

$pagetitle = get_string('pagetitle', 'tool_monitoring');

if ($courseid == 0 || !isset($courseid)) {
    $courseid = $COURSE->id;
}
$context = \context_course::instance($courseid);

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/monitoring/index.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($pagetitle);
$PAGE->requires->js_call_amd(
    'core/first',
    'require',
    array('charts/chartjs/Chart.min', 'Chart'),
    array('exports' => 'Chart'),
    true
);

echo $OUTPUT->header();


// Retrieve all user id in session with 'student' role associated.
$sqlstudent = 'SELECT u.id, u.username
               FROM {role_assignments} ra
                   JOIN {user} u on u.id = ra.userid
                   JOIN {role} r ON r.id = ra.roleid
               WHERE r.id = :roleid';
$idrolestudent = ['roleid' => 5];
$querystudents = $DB->get_records_sql($sqlstudent, $idrolestudent);

$querycountstudent = 'SELECT count(*) as num
                      FROM {role_assignments} ra
                          JOIN {user} u on u.id = ra.userid
                          JOIN {role} r ON r.id = ra.roleid
                      WHERE r.id = :roleid';
$resultcountstudent = $DB->get_record_sql($querycountstudent, $idrolestudent);
$numstudent = $resultcountstudent->num;

// Retrieve role user in session.
$queryroleid = 'SELECT r.id
                FROM {role_assignments} ra
                    JOIN {role} r on r.id = ra.roleid
                WHERE ra.userid = :userid';
$paramqueryroleid = ['userid' => $USER->id];
$resultroleid = $DB->get_record_sql($queryroleid, $paramqueryroleid);
$roleid = $resultroleid->id;

$calendarparam['view'] = 'month';
$calendarurl = new moodle_url('/calendar/view.php', $calendarparam);

$datestring = get_string('date', 'tool_monitoring');
$weight = get_string('weight', 'tool_monitoring');
$waistcircumference = get_string('waistcircumference', 'tool_monitoring');
$glicemy = get_string('glicemy', 'tool_monitoring');
$clinicaldata = get_string('clinicaldata', 'tool_monitoring');
$insusername = get_string('insusername', 'tool_monitoring');
$searchusername = get_string('searchusername', 'tool_monitoring');
$search = get_string('search', 'tool_monitoring');
$titlechart = get_string('titlechart', 'tool_monitoring');
$messageemptychart = get_string('messageemptychart', 'tool_monitoring');
$gocalendar = get_string('gocalendar', 'tool_monitoring');
$csvgen = get_string('csvgen', 'tool_monitoring');

$canaccessallcharts = has_capability('tool/monitoring:accessallcharts', $context);


if (!$canaccessallcharts) {
    $utility->singleuserchart(
        $messageemptychart,
        $weight,
        $waistcircumference,
        $glicemy,
        $titlechart,
        null
    );
} else {


    $data = array(
        'searchusername' => $searchusername,
        'formAction' => '',
        'insusername' => $insusername,
        'search' => $search
    );
    // Redirect calendar.
    $utility->rendermustachefile('templates/templatesearchbar.mustache', $data);



    if ($username) {
        // Retrieve all user id in session with 'student' role associated.
        $sqlstudent = 'SELECT u.id, u.username
                       FROM {role_assignments} ra
                           JOIN {user} u on u.id = ra.userid
                           JOIN {role} r ON r.id = ra.roleid
                       WHERE r.id = :roleid
                           AND username = :username';
        $paramsquery = ['roleid' => 5, 'username' => $username];
        $mysinglestudent = $DB->get_record_sql($sqlstudent, $paramsquery);

        if (!empty($mysinglestudent)) {

            $utility->singleuserchart(
                '',
                $glicemy,
                $weight,
                $waistcircumference,
                $clinicaldata . $username,
                $mysinglestudent->id
            );

            $username = $mysinglestudent->username;
            $userid = $mysinglestudent->id;

            $results = $utility->executequeries($userid);

            // CHART WEIGHT.
            $arraypeso = $utility->preparearray($results[0]);
            // CHART WAIST CIRCUMFERENCE.
            $arrayvita = $utility->preparearray($results[1]);
            // CHART GLICEMY.
            $arrayglicemia = $utility->preparearray($results[2]);

            $mergedarray = $utility->createmergedarray($arraypeso, $arrayvita, $arrayglicemia);


            $filename='';
            if (isset($singlecsv)) {
                $delimiter = ';';

                $date = userdate(time(), '%d%m%Y', 99, false, false);
                $filename = 'file_' . $date . '_' . $username . '.csv';
                $filepath = $CFG->dirroot . '/admin/tool/monitoring/' . $filename;
                $utility->writingfile($datestring, $weight, $waistcircumference, $glicemy, $filename, $delimiter, $mergedarray);
            }


            $data = array(
                'filenamearray' => $filename,
                'singlecsv' => $username,
                'csvgen' => $csvgen,
            );
            $utility->rendermustachefile('templates/templatecsv.mustache', $data);
        } else {
            $messagenotfound = get_string('messagenotfound', 'tool_monitoring');
            echo \html_writer::tag('div class="padding-top-bottom"', '<h5>' . $messagenotfound . $username . '</h5>');
        }
    } else {
        $filenamearray = array();
        foreach ($querystudents as $student) {

            $username = $student->username;
            $userid = $student->id;

            $results = $utility->executequeries($userid);

            // CHART WEIGHT.
            $arraypeso = $utility->preparearray($results[0]);

            // CHART WAIST CIRCUMFERENCE.
            $arrayvita = $utility->preparearray($results[1]);
            // CHART GLICEMY.
            $arrayglicemia = $utility->preparearray($results[2]);

            // If partecipant into for loop haven't compiled the surveypro.
            if (!empty($arraypeso[0])) {

                $title = $clinicaldata . $username;

                echo \html_writer::tag(
                    'div class="padding-top-bottom"',
                    $utility->generatechart(
                        $arraypeso,
                        $arrayvita,
                        $arrayglicemia,
                        $weight,
                        $waistcircumference,
                        $glicemy,
                        $title
                    )
                );


                $mergedarray = $utility->createmergedarray($arraypeso, $arrayvita, $arrayglicemia);

                // Each element of the array is an array in turn and each element will contain
                // one element of each field (weight, waist circumference, blood sugar).

                $delimiter = ';';

                $date = userdate(time(), '%d%m%Y', 99, false, false);
                $filename = 'file_' . $date . '_' . $username . '.csv';
                $filepath = $CFG->dirroot . '/admin/tool/monitoring/' . $filename;

                if (isset($csv)) {
                    array_push($filenamearray, $filename);
                    // Set headers to force download.
                    $utility->writingfile($datestring, $weight, $waistcircumference, $glicemy, $filename, $delimiter, $mergedarray);
                }
            }
        }
        $data = array(
            'filenamearray' => $filenamearray,
            'csvgen' => $csvgen,
        );
        // Download csv files.
        $utility->rendermustachefile('templates/templatecsv.mustache', $data);
    }
}
$data = array(
    'calendarurl' => $calendarurl,
    'gocalendar' => $gocalendar,

);
// Redirect calendar.
$utility->rendermustachefile('templates/templatecalendar.mustache', $data);

echo $OUTPUT->footer();
