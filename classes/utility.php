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
 * Monitoring utility class.
 *
 * @package   tool_monitoring
 * @copyright 2013 onwards kordan <kordan@mclink.it>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_monitoring;

/**
 * Utility class.
 *
 * @package    tool_monitoring
 * @copyright  2023 Davide Mirra <davide.mirra@iss.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utility {
    /**
     * Returns an array containing two elements: the content and timecreated of all chart parameters in the input recordset.
     *
     * @param moodle_recordset $result The recordset containing the chart parameters.
     * @return array An array containing two elements: the content and timecreated of all chart parameters.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function preparearray($result) {
        $content = array();
        $timecreated = array();
        // Iterate over each row in the recordset and extract the content and timecreated values.
        foreach ($result as $row) {
            $content[] = $row->content;
            $timecreated[] = date('d/m/Y', $row->timecreated);
        }
        // Return the content and timecreated arrays as a single array.
        return array($content, $timecreated);
    }

    /**
     * Returns the chart generated from the input data arrays.
     *
     * @param array $arraypeso An array containing weight values.
     * @param array $arrayvita An array containing waist values.
     * @param array $arrayglicemia An array containing blood glucose values.
     * @param string $title Optional; The chart title.
     * @return string HTML containing the chart.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function generatechart($arraypeso, $arrayvita, $arrayglicemia, $weight, $waistcircumference, $glicemy, $title = '') {
        global $OUTPUT;

        // Create chart series for each data array.
        $answer1 = new \core\chart_series($weight, $arraypeso[0]);
        $answer2 = new \core\chart_series($waistcircumference, $arrayvita[0]);
        $answer3 = new \core\chart_series($glicemy, $arrayglicemia[0]);

        // Create a new line chart and set its properties.
        $chart = new \core\chart_line();
        $chart->set_title($title);
        $chart->set_legend_options(['position' => 'bottom', 'reverse' => true]);

        // Add the chart series to the chart.
        $chart->add_series($answer1);
        $chart->add_series($answer2);
        $chart->add_series($answer3);

        // Set the x-axis labels to the date values in the input arrays.
        $chart->set_labels($arraypeso[1]);
        $chart->set_labels($arrayvita[1]);
        $chart->set_labels($arrayglicemia[1]);

        // Render the chart using the Moodle output renderer.
        echo $OUTPUT->render($chart);
    }

    /**
     * Returns the final result of all executed queries.
     *
     * @param int $userid The ID of the user to retrieve data for. If not provided, the current user is used.
     * @return array $results An array containing the query results.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function executequeries($userid = null) {
        global $DB, $USER;

        // If $userid argument is not provided, use the current user.
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        // Initialize the results array.
        $results = array();


        // Query to select SurveyPro module submissions based on user and specific module item.
        $query = 'SELECT s.timecreated, a.content
              FROM {surveypro_submission} s
                  JOIN {surveypro_answer} a ON a.submissionid = s.id
              WHERE s.status = :status
              AND s.userid = :userid
              AND a.itemid = :itemid';

        // Parameters and query to select the "peso" item from SurveyPro module.
        $itemsql = 'SELECT num.itemid
                   FROM {surveyprofield_numeric} num
                   WHERE num.variable = :pesovarname';
        $resultitem = $DB->get_record_sql($itemsql, ['pesovarname' => 'peso']);
        $queryparam = ['status' => 0, 'userid' => $userid, 'itemid' => $resultitem->itemid];
        $result = $DB->get_records_sql($query, $queryparam);
        array_push($results, $result);


        // Parameters and query to select the "misurazione vita" item from SurveyPro module.
        $itemsql = 'SELECT num.itemid
                       FROM {surveyprofield_numeric} num
                       WHERE num.variable = :circvita';
        $resultitem = $DB->get_record_sql($itemsql, ['circvita' => 'misurazione vita']);
        $queryparam = ['status' => 0, 'userid' => $userid, 'itemid' => $resultitem->itemid];               
        $result = $DB->get_records_sql($query, $queryparam);
        array_push($results, $result);

        // Parameters and query to select the "glicemia" item from SurveyPro module.
        $itemsql = 'SELECT num.itemid
                       FROM {surveyprofield_numeric} num
                       WHERE num.variable = :glicemiavarname';

        $resultitem = $DB->get_record_sql($itemsql, ['glicemiavarname' => 'glicemia']);
        $glicemiaparam = ['status' => 0, 'userid' => $userid, 'itemid' => $resultitem->itemid];         
        $result = $DB->get_records_sql($query, $glicemiaparam);
        array_push($results, $result);
   

        // Return the results array.
        return $results;
    }

    /**
     * Renders HTML output from a Mustache template file.
     *
     * @param string $pathfile The path to the Mustache template file.
     * @param object $param The data object to pass to the template.
     * @param string $nameparam The name of the data object parameter in the template.
     * @return echo html render The HTML output rendered from the Mustache template.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function rendermustachefile($pathfile, $data) {
        if (file_exists($pathfile)) {
            // Create a new Mustache engine and load the template file.
            $mustache = new \Mustache_Engine();
            $template = file_get_contents($pathfile);
            // Render the template with the specified data object.
            echo $mustache->render($template, $data);
        } else {
            // If the template file doesn't exist, output an error message.
            echo "The file $pathfile does not exist.";
        }
    }

    /**
     * Renders HTML output for a single user's chart.
     *
     * @param object $utility The chart utility object.
     * @param string $message The message to display if the user has not completed the survey.
     * @param string $title The chart title.
     * @param int $userid Optional; The ID of the user to generate the chart for. Defaults to the current user.
     * @return echo html render The HTML output for the user's chart and mergedarray
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function singleuserchart($message, $title, $weight, $waistcircumference, $glicemy, $userid = null) {
        global $USER;

        // Set the user ID to the current user if not specified.
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        // Execute the queries to retrieve the chart data.
        $results = $this->executequeries($userid);

        // Prepare the chart data arrays.
        $arraypeso = $this->preparearray($results[0]);
        $arrayvita = $this->preparearray($results[1]);
        $arrayglicemia = $this->preparearray($results[2]);

        // If the user has not completed the survey, display a message.
        if (empty($arraypeso[0])) {
            echo \html_writer::tag('h5', $message);
        } else {
            // Otherwise, generate the chart with the specified title.
            $title = $title;
            echo \html_writer::tag(
                'div class="padding-top-bottom"',
                $this->generatechart($arraypeso, $arrayvita, $arrayglicemia, $title, $weight, $waistcircumference, $glicemy)
            );
        }
    }

    /**
     * This function takes in three arrays - $arraypeso, $arrayvita, and $arrayglicemia -
     * and creates a merged array that contains all the fields needed in an Excel sheet for each record.
     *
     * @param array $arraypeso     The array of weights.
     * @param array $arrayvita     The array of vital signs.
     * @param array $arrayglicemia The array of blood glucose levels.
     * @return array               The merged array containing all the fields needed for each record.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function createmergedarray($arraypeso, $arrayvita, $arrayglicemia) {
        // Create an empty array to hold the merged data.
        $mergedarray = array();

        // Get the length of any of the arrays (assuming they all have the same length).
        $length = count($arraypeso[0]);

        // Iterate through all arrays at once.
        for ($j = 0; $j < $length; $j++) {
            // Create a new array containing the current elements from all three input arrays.
            // The second element of $arraypeso is the creation date, which is always the same
            // for all three parameters.
            $elementarray = array($arraypeso[1][$j], $arraypeso[0][$j], $arrayvita[0][$j], $arrayglicemia[0][$j]);
            
            // Add the new array to the merged array.
            array_push($mergedarray, $elementarray);
        }

        // Return the merged array.
        return $mergedarray;
    }

    /**
     * This function writes data to a CSV file.
     *
     * @param string $date              The date of the measurement.
     * @param string $weight            The weight of the patient.
     * @param string $waistcircumference The waist circumference of the patient.
     * @param string $glicemy           The blood glucose level of the patient.
     * @param string $filename          The name of the file to write to.
     * @param string $delimiter         The delimiter to use in the CSV file.
     * @param array  $mergedarray       The merged array of data to write to the CSV file.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function writingfile($date, $weight, $waistcircumference, $glicemy, $filename, $delimiter, $mergedarray) {
        global $CFG;

        // Open the file for writing.
        $exportsubdir = 'tool_monitoring/csv';
        make_temp_directory($exportsubdir);
        $filewithpath = $CFG->tempdir . '/' . $exportsubdir . '/' . $filename;
        $filehandler = fopen($filewithpath, 'w');

        // Check if the file was opened successfully.
        if ($filehandler) {
            // Create an array of the header row.
            $csv = array(
                array($date, $weight, $waistcircumference, $glicemy)
            );

            // Loop through the merged array and create a new array for each row of data.
            foreach ($mergedarray as $elementarray) {
                for ($j = 0; $j < count($mergedarray); $j++) {
                    $newelement = array(
                        $date => $elementarray[$j], $weight => $elementarray[$j + 1],
                        $waistcircumference => $elementarray[$j + 2],
                        $glicemy => $elementarray[$j + 3]
                    );
                    array_push($csv, $newelement);
                    break;
                }
            }

            // Loop through the data and write each row to the CSV file.
            foreach ($csv as $row) {
                fputcsv($filehandler, $row, $delimiter);
            }
            fclose($filehandler);

        }
    }
}
