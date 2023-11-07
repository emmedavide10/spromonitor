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

$utility = new utility();

// If you search by username in the search bar.
$username = optional_param('username', null, PARAM_TEXT);
$singlecsv = optional_param('singlecsv', null, PARAM_TEXT);
$csv = optional_param('csv', 0, PARAM_INT);

$courseid = $utility->getCourseId();

$context = \context_course::instance($courseid);

if (!isset($username)) {
    $username = $singlecsv;
}

$pagetitle = get_string('pagetitle', 'tool_monitoring');
$param = ['courseid' => $courseid];

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/monitoring/index.php', $param);
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

$sqlusers = 'SELECT u.id, u.username
               FROM {role_assignments} ra
                   JOIN {user} u on u.id = ra.userid
                   JOIN {context} ctx ON ctx.id = ra.contextid
                   WHERE ctx.instanceid = :courseid';
$idcourse = ['courseid' => $courseid];
$queryusers = $DB->get_records_sql($sqlusers, $idcourse);

$querycountusers = 'SELECT count(*) as num
                      FROM {role_assignments} ra
                          JOIN {user} u on u.id = ra.userid';
$resultcountuser = $DB->get_record_sql($querycountusers);
$numuser = $resultcountuser->num;

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

$filenamearray = array();

if (!$canaccessallcharts) {
    $utility->singleuserchart(
        $courseid,
        $messageemptychart,
        $titlechart,
        $weight,
        $waistcircumference,
        $glicemy,
        null
    );
} else {

    $data = array(
        'searchusername' => $searchusername,
        'formAction' => '',
        'insusername' => $insusername,
        'search' => $search,
        'courseid' => $courseid,
    );

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
        // Dopo aver ottenuto i risultati dalla query

        if (count($usersearched) == 1) {

            $utility->singleuserchart(
                $courseid,
                '',
                $clinicaldata . $username,
                $weight,
                $waistcircumference,
                $glicemy,
                $userid
            );

            $results = $utility->executequeries($courseid, $userid);

            // CHART WEIGHT.
            $arraypeso = $utility->preparearray($results[0]);
            // CHART WAIST CIRCUMFERENCE.
            $arrayvita = $utility->preparearray($results[1]);
            // CHART GLICEMY.
            $arrayglicemia = $utility->preparearray($results[2]);

            $mergedarray = $utility->createmergedarray($arraypeso, $arrayvita, $arrayglicemia);

            $filename = $utility->generateFilename($username, $csv, $datestring, $weight, $waistcircumference,
            $glicemy, $mergedarray);

            array_push($filenamearray, $filename); // Aggiungi il nome del file all'array.

            $data = array(
                'filenamearray' => $filenamearray,
                'singlecsv' => $username,
                'csvgen' => $csvgen,
                'courseid' => $courseid,
            );

            $utility->rendermustachefile('templates/templatecsv.mustache', $data);
        } elseif (count($usersearched) == 0) {

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

            $results = $utility->executequeries($courseid, $userid);

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

                $filename = $utility->generateFilename($username, $csv, $datestring, $weight, $waistcircumference,
                $glicemy, $mergedarray);

                array_push($filenamearray, $filename); // Aggiungi il nome del file all'array.
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

);
// Redirect calendar.
$utility->rendermustachefile('templates/templatecalendar.mustache', $data);

echo $OUTPUT->footer();
