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
 * @copyright  2023 Davide Mirra <davide.mirra@iss.it>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_monitoring;

/**
 * Utility class.
 * @package    tool_monitoring
 * @copyright  2023 Davide Mirra <davide.mirra@iss.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Utility
{


    /**
     * Returns an array containing two elements: the content and timecreated of all chart parameters
     * in the input recordset.
     *
     * @param moodle_recordset $result The recordset containing the chart parameters.
     * @return array An array containing two elements: the content and timecreated of all chart parameters.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function prepareArray($result)
    {
        $content = array();
        $timecreated = array();

        // Iterate over each row in the recordset and extract the values of content and timecreated.
        foreach ($result as $row) {
            $content[] = $row->content;
            $timecreated[] = date('d/m/Y', $row->timecreated);
        }

        // Return the content and timecreated arrays as a single associative array.
        return array('content' => $content, 'timecreated' => $timecreated);
    }



    /**
     * Generates a line chart based on input data arrays.
     *
     * @param string $title Optional; The chart title.
     * @param array $variablesArray An array containing variable names.
     * @param array $chartDataArrays An array containing chart data arrays with 'content' and 'timecreated' keys.
     *
     * @return string HTML containing the generated chart.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function generateChart(
        $title = '',
        $variablesArray,
        $chartDataArrays
    ): string {
        global $OUTPUT;
    
    
        // Create chart series for each data array.
        $chartSeries = [];
    
        foreach ($variablesArray as $index => $variable) {
            $contentArray = $chartDataArrays[$index]['content'];
            $timecreatedArray = $chartDataArrays[$index]['timecreated'];
    
            // Create a new chart series for each variable.
            $chartSeries[] = new \core\chart_series($variable, $contentArray);
        }
    
        // Uncomment the line below for debugging purposes.
    
        // Create a new line chart and set its properties.
        $chart = new \core\chart_line();
        $chart->set_title($title);
        $chart->set_legend_options(['position' => 'bottom', 'margin-bottom' => 30]);
    
        // Add series to the chart in the normal order.
        foreach ($chartSeries as $series) {
            $chart->add_series($series);
        }
    
        // Set the x-axis labels to the date values in the input arrays.
        $chart->set_labels($timecreatedArray);
    
        // Add spacing between charts (adjust the margin as needed).
        $chartHtml = '<div style="margin-bottom: 70px;">' . $OUTPUT->render($chart) . '</div>';
    
        return $chartHtml;
    }
    



    /**
     * Executes queries to retrieve data for generating charts.
     *
     * @param int|null $userid The ID of the user to retrieve data for. If not provided, the current user is used.
     * @param array $selectedFieldsArray An array containing selected field IDs.
     *
     * @return array An array containing the query results.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function executeQueries($userid = null, $selectedFieldsArray): array
    {
        global $DB, $USER;

        // If $userid argument is not provided, use the current user.
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        // Initialize the results array.
        $results = [];

        // Query to select SurveyPro module submissions based on user and specific module item.
        $query = 'SELECT s.timecreated, a.content
              FROM {surveypro_submission} s
                  JOIN {surveypro_answer} a ON a.submissionid = s.id
              WHERE s.status = :status
              AND s.userid = :userid
              AND a.itemid = :itemid';

        foreach ($selectedFieldsArray as $itemid) {
            // Execute the query for each item ID in the array.
            $queryparam = ['status' => 0, 'userid' => $userid, 'itemid' => (int)$itemid];
            $result = $DB->get_records_sql($query, $queryparam);
            array_push($results, $result);
        }

        // Return the results array.
        return $results;
    }


    /**
     * Renders HTML output from a Mustache template file.
     *
     * @param string $pathfile The path to the Mustache template file.
     * @param object $data The data object to pass to the template.
     * @return echo html render The HTML output rendered from the Mustache template.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function rendermustachefile($pathfile, $data)
    {
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
     * @param string $message The message to display if the user has not completed the survey.
     * @param string $title The chart title.
     * @param array $variablesArray An array containing variable names.
     * @param array $selectedFieldsArray An array containing selected field IDs.
     * @param int $userid Optional; The ID of the user to generate the chart for. Defaults to the current user.
     *
     * @return echo html render The HTML output for the user's chart and mergedarray.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function singleUserChart($message, $title, $variablesArray, $selectedFieldsArray, $userid = null)
    {

        global $USER;

        // Set the user ID to the current user if not specified.
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        // Execute the queries to retrieve the chart data.
        $results = $this->executeQueries($userid, $selectedFieldsArray);

        $empty = true;
        foreach ($results as $subArr) {
            if (count($subArr) != 0) {
                $empty = false;
            }
        }

        // Prepare the chart data arrays.
        $chartDataArrays = [];
        foreach ($results as $result) {
            $chartDataArrays[] = $this->prepareArray($result);
        }

        // If the user has not completed the survey, display a message.
        if ($empty) {
            echo \html_writer::tag('h5', $message);
        } else {
            // Combine the selected fields into an array

            // Otherwise, generate the chart with the specified title.
            echo \html_writer::tag(
                'div class="padding-top-bottom"',
                $this->generateChart(
                    $title,
                    $variablesArray,
                    $chartDataArrays
                )
            );
        }
    }



    /**
     * Creates a merged array from multiple arrays.
     *
     * @param array $variablesArray An array containing variable names.
     *
     * @return array The merged array containing all the fields needed for each record.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function createMergedArray($variablesArray)
    {
        // Create an empty array to hold the merged data.
        $mergedArray = array();

        // Get the length of any of the arrays (assuming they all have the same length).
        $length = count($variablesArray);

        // Iterate through all arrays at once.
        for ($j = 0; $j < $length; $j++) {
            // Create a new array containing the current elements from all arrays.
            $elementArray = array();

            foreach ($variablesArray as $variableArray) {
                $elementArray[] = $variableArray[$j];
            }

            // Add the new array to the merged array.
            array_push($mergedArray, $elementArray);
        }

        // Return the merged array.
        return $mergedArray;
    }



    /**
     * Writes data to a CSV file.
     *
     * @param string $dateString The date of the measurement.
     * @param array $variablesArray An array containing variable names.
     * @param string $filename The name of the file to write to.
     * @param string $delimiter The delimiter to use in the CSV file.
     * @param array $mergedArray The merged array of data to write to the CSV file.
     *
     * @return void
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function writingFile(
        $dateString,
        $variablesArray,
        $filename,
        $delimiter,
        $mergedArray
    ) {
        global $CFG;

        // Open the file for writing.
        $exportSubdir = 'tool_monitoring/csv';
        make_temp_directory($exportSubdir);
        $fileWithPath = $CFG->tempdir . '/' . $exportSubdir . '/' . $filename;
        $fileHandler = fopen($fileWithPath, 'w');

        // Check if the file was opened successfully.
        if ($fileHandler) {
            // Create an array of the header row.
            $header = array($dateString);

            // Add the column names to the header.
            $header = array_merge($header, $variablesArray);

            // Write the header to the CSV file.
            fputcsv($fileHandler, $header, $delimiter);

            // Loop through the mergedArray and write each row to the CSV file.
            foreach ($mergedArray as $elementArray) {
                // Create a new data row using the column names specified in $variablesArray.
                $rowData = array($elementArray[0]); // Add the date column.

                // Add the values of the specified columns in $variablesArray.
                foreach ($variablesArray as $variable) {
                    $rowData[] = $elementArray[$variable];
                }

                // Write the row to the CSV file.
                fputcsv($fileHandler, $rowData, $delimiter);
            }

            fclose($fileHandler);
        }
    }


    /**
     * Assigns the value of $courseid based on the GET, POST, and session variables.
     *
     * @return int The value of $courseid assigned based on the GET, POST, and session variables.
     */
    public function getCourseId()
    {

        // Check if the value is present in the GET variables
        $courseid = optional_param('courseid', 0, PARAM_INT);
        if ($courseid == 0) {
            $courseid = optional_param('context_id', 0, PARAM_INT);
        }

        return $courseid;
    }



    /**
     * Function to handle the conversion of $selectedFields into an array by splitting values based on commas.
     *
     * @param mixed $fields The fields to handle.
     *
     * @return array The array resulting from handling the fields.
     */
    public function handleSelectedFields($fields)
    {
        if (!empty($fields)) {
            return is_array($fields) ? $fields : explode(',', $fields);
        }
        return [];
    }

    /**
     * Generates the file name.
     *
     * @param string $username The username to use in the file name.
     * @param bool $csv A flag indicating whether a CSV file is requested.
     * @param string $datestring A date string to use in the file name.
     * @param array $variablesArray An array containing variable names.
     * @param array $mergedArray A merged array containing all the fields needed for each record.
     *
     * @return string The generated file name.
     */
    public function generateFilename($username, $csv, $datestring, $variablesArray, $mergedArray)
    {
        global $CFG;

        $delimiter = ';';

        $date = userdate(time(), '%d%m%Y', 99, false, false); // Gets a formatted date as a string.
        $filename = 'file_' . $date . '_' . $username . '.csv'; // Creates the file name.
        $filepath = $CFG->dirroot . '/admin/tool/monitoring/' . $filename; // Creates the full file path.

        if (isset($csv)) { // Checks if a CSV file is requested.

            // Calls the writingFile() function to write the CSV file (the source code for writingFile() is not included in this description).
            $this->writingFile(
                $datestring,
                $variablesArray,
                $filename,
                $delimiter,
                $mergedArray
            );
        }

        return $filename; // Returns the generated file name.
    }
}
