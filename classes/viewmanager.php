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
 * View class.
 *
 * @package   spromonitor
 * @copyright  2024 Davide Mirra <davide.mirra@iss.it>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_spromonitor;


class viewmanager {

    /**
     * @var object Course module object.
     */
    protected $cm;

    /**
     * @var object Context object.
     */
    protected $context;

    /**
     * @var object spromonitor object.
     */
    protected $spromonitor;

    /**
     * Class constructor.
     *
     * @param object $cm
     * @param object $context
     * @param object $surveypro
     */
    public function __construct($cm, $context, $spromonitor) {
        $this->cm = $cm;
        $this->context = $context;
        $this->spromonitor = $spromonitor;
    }

    /**
     * Notify activity needs setup.
     *
     * @return void
     */
    public function display_activityneedssetup() {
        global $OUTPUT;

        $message = get_string('activityneedssetup', 'spromonitor');
        echo $OUTPUT->notification($message, 'notifyproblem');
    }

    /**
     * Display chart.
     *
     * @return void
     */
    public function display_chart() {
        global $DB, $PAGE, $CFG;

        $spromonitor = $this->spromonitor;
        $cm = $this->cm;
        $context = $this->context;

        $utility = new \mod_spromonitor\utility();
        // Retrieve parameters safely.
        $username = optional_param('username', null, PARAM_TEXT);
        $singlecsv = optional_param('singlecsv', null, PARAM_TEXT);
        $courseid = $spromonitor->course;
        $sproid = $spromonitor->surveyproid;
        $selectedfieldsid = $spromonitor->fieldscsv;
        $selecteddateid = $spromonitor->measuredateid;

        $selectedfieldsarray = [];
        $variablesarray = [];

        // Handle selected fields and extract variables.
        if (isset($selectedfieldsid)) {
            $selectedfieldsarray = $utility->handleselectedfields($selectedfieldsid);
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

        if (isset($selecteddateid)) {
            // Execute SQL query to get the variable associated with the selected date.
            $sqldata = "SELECT variable FROM {surveyprofield_date} WHERE itemid = :field";
            $fieldate = ['field' => $selecteddateid];
            $result = $DB->get_record_sql($sqldata, $fieldate);
            $dateselectedvariable = $result->variable;
        }

        // If the username is not set, use the singlecsv parameter.
        if (!isset($username)) {
            $username = $singlecsv;
        }

        // Get the page title.
        $pagetitle = get_string('pagetitle', 'spromonitor');

        // Set up URL parameters for the page.
        $paramsurl['id'] = $cm->id;

        // Set up the Moodle page.
        $PAGE->set_context($context);
        $PAGE->set_url('/mod/spromonitor/view.php', $paramsurl);
        $PAGE->set_pagelayout('standard');

        // Include necessary JavaScript for Chart.js.
        $PAGE->requires->js_call_amd(
            'core/first',
            'require',
            ['charts/chartjs/Chart.min', 'Chart'],
            ['exports' => 'Chart'],
            true
        );

        // Set up URL for the course dashboard.
        $paramurl['id'] = $courseid;
        $paramurl['section'] = 0;
        $urldashboard = new \moodle_url($CFG->wwwroot . '/course/view.php', $paramurl);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $coursename = $course->fullname;
        $hoverlinktest = get_string('hoverlinktest', 'spromonitor');

          // Data for the header template.
        $dataheader = [
            'pagetitle' => $pagetitle,
            'urldashboard' => $urldashboard,
            'courseName' => $coursename,
            'hoverlinktest' => $hoverlinktest,
        ];

        $utility->rendermustachefile('templates/templateheader.mustache', $dataheader);

        // Check user's capability to access all charts.
        $canaccessallcharts = has_capability('mod/spromonitor:accessallcharts', $context);

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
        $datedefault = get_string('date', 'spromonitor');
        $clinicaldata = get_string('clinicaldata', 'spromonitor');
        $insusername = get_string('insusername', 'spromonitor');
        $searchusername = get_string('searchusername', 'spromonitor');
        $search = get_string('search', 'spromonitor');
        $titlechart = get_string('titlechart', 'spromonitor');
        $messageemptychart = get_string('messageemptychart', 'spromonitor');
        $csvgen = get_string('csvgen', 'spromonitor');
        $goback = get_string('goback', 'spromonitor');

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
                $selecteddateid,
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
                'selectedfields' => $selectedfieldsid,
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
                        $selecteddateid,
                        $userid
                    );

                    // Execute queries and prepare data for the user.
                    $chartdataarray = $utility->executequeries($selectedfieldsarray, $selecteddateid, $userid);

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
                    $messagenotfound = get_string('messagenotfound', 'spromonitor');
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

                    $chartdataarray = $utility->executequeries($selectedfieldsarray, $selecteddateid, $userid);

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
            'goback' => $goback,
        ];

        // Redirect to calendar.
        $utility->rendermustachefile('templates/templatecalendarandbb.mustache', $data);
    }
}
