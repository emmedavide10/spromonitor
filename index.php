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


// Ottenere i parametri POST in modo sicuro
$courseid = optional_param('courseid', 0, PARAM_INT);
$sproid = optional_param('sproid', 0, PARAM_INT);
$selectedFields = optional_param('selectedFields', '', PARAM_TEXT);
$selectedFieldsSearch = optional_param('selectedFieldsSearch', '', PARAM_TEXT);
/*
echo "selectedFieldsSearch";
print_r($selectedFieldsSearch);

echo "selectedFields";
print_r($selectedFields);

die;*/


if (isset($selectedFields)) {
    $selectedFieldsArray = $utility->handleSelectedFields($selectedFields);
} else if(isset($selectedFieldsSearch)){
    $selectedFieldsArray = $utility->handleSelectedFields($selectedFieldsSearch);
}

print_r($selectedFieldsArray);
die;

$variablesArray = [];
foreach ($selectedFieldsArray as $field) {
    // Esegui la query per ottenere le variabili associate agli itemid noti
    $sql = "SELECT variable FROM {surveyprofield_numeric} WHERE itemid = :field";
    $params = ['field' => $field];
    $result = $DB->get_record_sql($sql, $params);
    // Verifica se ci sono risultati prima di accedere alla proprietà variable
    if (!empty($result) && isset($result->variable)) {
        // Aggiungi il valore della variabile alla fine dell'array
        array_push($variablesArray, $result->variable);
    } else {
        // Se non ci sono risultati, aggiungi un valore vuoto all'array (o un valore di default, a seconda delle tue esigenze)
        $variablesArray[] = null; // Puoi cambiare questo con il valore di default desiderato
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
$paramsurl['selectedFieldsSearch'] = $selectedFieldsSearch;

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/monitoring/index.php', $paramsurl);
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
                          JOIN {user} u on u.id = ra.userid
                          JOIN {context} ctx ON ctx.id = ra.contextid
                   WHERE ctx.instanceid = :courseid';
$idcourse = ['courseid' => $courseid];
$resultcountuser = $DB->get_record_sql($querycountusers, $idcourse);
$numuser = $resultcountuser->num;


$calendarparam['view'] = 'month';
$calendarurl = new moodle_url('/calendar/view.php', $calendarparam);

$datestring = get_string('date', 'tool_monitoring');
/*$weight = get_string('weight', 'tool_monitoring');
    $waistcircumference = get_string('waistcircumference', 'tool_monitoring');
    $glicemy = get_string('glicemy', 'tool_monitoring');*/
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
    // Utilizza i valori nell'array per la tua funzione
    $utility->singleuserchart($messageemptychart, $titlechart, $variablesArray, $selectedFieldsArray, null);
} else {

    $selectedFieldsSearch = json_encode($selectedFields);
    $selectedFieldsSearch = str_replace('"', '', $selectedFieldsSearch);

    //print_r($selectedFieldsSearch); die;
    $data = array(
        'searchusername' => $searchusername,
        'formAction' => '',
        'insusername' => $insusername,
        'search' => $search,
        'courseid' => $courseid,
        'selectedFieldsSearch' => $selectedFieldsSearch,
    );


    //TODO passare nel mustache della searchar i selectedfields perchè sennò non me li ritrovo quando clicco sul search



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
        $utility->singleuserchart($messageemptychart, $titlechart, $variablesArray, $selectedFieldsArray, $userid);

        if (count($usersearched) === 1) {

            $utility->singleuserchart(
                '',
                $clinicaldata . $username,
                $weight,
                $waistcircumference,
                $glicemy,
                $userid
            );

            $results = $utility->executequeries($userid, $selectedFieldsArray);

            // CHART WEIGHT.
            $arraypeso = $utility->preparearray($results[0]);
            // CHART WAIST CIRCUMFERENCE.
            $arrayvita = $utility->preparearray($results[1]);
            // CHART GLICEMY.
            $arrayglicemia = $utility->preparearray($results[2]);

            $mergedarray = $utility->createmergedarray($arraypeso, $arrayvita, $arrayglicemia);

            $filename = $utility->generateFilename(
                $username,
                $csv,
                $datestring,
                $weight,
                $waistcircumference,
                $glicemy,
                $mergedarray
            );

            array_push($filenamearray, $filename); // Aggiungi il nome del file all'array.

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
        echo "no search";
        if ($usersearched) {
            $queryusers = $usersearched;
        }

        //var_dump($queryusers); die;

        foreach ($queryusers as $user) {

            $username = $user->username;
            $userid = $user->id;
            //var_dump($selectedFieldsArray); die;

            $results = $utility->executequeries($userid, $selectedFieldsArray);
            //var_dump($results); die;

            $chartDataArrays = [];
            foreach ($results as $result) {
                $preparedData = $utility->preparearray($result);

                if (!empty($preparedData['content']) && !empty($preparedData['timecreated'])) {
                    $chartDataArrays[] = $preparedData;
                }
            }

            // If partecipant into for loop haven't compiled the surveypro.
            if (!empty($chartDataArrays)) {

                //var_dump($chartDataArrays); die;

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
/*
// back button
$utility->rendermustachefile('templates/templatebackbutton.mustache', $data);*/

echo $OUTPUT->footer();
