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
 * Spromonitor utility class.
 *
 * @package   mod_spromonitor
 * @copyright  2024 Davide Mirra <davide.mirra@iss.it>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_spromonitor;

class utility {

    /**
     * Check if all elements in the given array are equal.
     * @return bool True if all elements are equal, false otherwise.
     */
    private function checkequalelements($array) {
        // If the array is empty, return true since there are no elements to compare.
        if (empty($array)) {
            return true;
        }
        // Compare each element with the first element of the array.
        $firstelement = $array[0];
        foreach ($array as $element) {
            if ($element !== $firstelement) {
                return false; // If it finds a different element, return false.
            }
        }
        return true; // If all elements are equal, return true.
    }

    /**
     * Generates a line chart based on input data arrays.
     *
     * @param string $title Optional; The chart title.
     * @param array $variablesarray An array containing variable names.
     * @param array $transformedarray An array containing chart data arrays with 'content' and 'timecreated' keys.
     *
     * @return string HTML containing the generated chart.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function generatechart($variablesarray, $transformedarray, $title = ''): string {

        global $OUTPUT;
        // Create chart series for each data array.
        $chartseries = [];
        $chartvaluesarray = [];

        $checkfirstdaterray = $this->checkequalelements($transformedarray[0]['timecreated']);

        if (!$checkfirstdaterray) {
            // Convert the input array into the required format for chart generation.
            $arrayconverted = $this->convertarray($variablesarray, $transformedarray);

            // Revert the converted array back to the original format.
            $chartvaluesarray = $this->revertarray($arrayconverted);
        } else {
            $chartvaluesarray = $transformedarray;
        }

        // Iterate through each variable to create chart series.
        foreach ($variablesarray as $index => $variable) {
            $contentarray = $chartvaluesarray[$index]['content'];
            $timecreatedarray = $chartvaluesarray[$index]['timecreated'];

            // Create a new chart series for each variable.
            $chartseries[] = new \core\chart_series($variable, $contentarray);
        }
        // Create a new line chart and set its properties.
        $chart = new \core\chart_line();
        $chart->set_title($title);
        $chart->set_legend_options(['position' => 'bottom']);
        // Add series to the chart in the normal order.
        foreach ($chartseries as $series) {

            $chart->add_series($series);
        }
        // Set the x-axis labels to the date values in the input arrays.
        $chart->set_labels($timecreatedarray);
        // Add spacing between charts (adjust the margin as needed).
        $charthtml = '<div style="margin-bottom: 7%">' . $OUTPUT->render($chart) . '</div>';
        return $charthtml;
    }

    /**
     * Converts the input array into aggregated format based on date.
     *
     * @param array $variablesarray An array containing variable names.
     * @param array $transformedarray An array containing chart data arrays with 'content' and 'timecreated' keys.
     *
     * @return array The converted array in aggregated format.
     */
    private function convertarray($variablesarray, $transformedarray): array {
        // Array to store aggregated data based on date.
        $arrayconverted = [];
        // Create the aggregated array.
        foreach ($variablesarray as $index => $variable) {
            $contentarray = $transformedarray[$index]['content'];
            $timecreatedarray = $transformedarray[$index]['timecreated'];

            // Aggregate data based on date.
            foreach ($timecreatedarray as $key => $date) {
                if (!isset($arrayconverted[$date])) {
                    $arrayconverted[$date] = [];
                }
                $arrayconverted[$date][] = $contentarray[$key];
            }
        }

        // Function for date comparison to sort the array.
        $datecompare = function ($a, $b) {
            $firstdate = strtotime(str_replace('/', '-', $a));
            $seconddate = strtotime(str_replace('/', '-', $b));
            return $firstdate - $seconddate;
        };

        // Sort the array based on date key.
        uksort($arrayconverted, $datecompare);

        return $arrayconverted;
    }

    /**
     * Reverts the aggregated array back to the original format.
     *
     * @param array $arrayconverted The array in aggregated format.
     *
     * @return array The reverted array in original format.
     */
    private function revertarray($arrayconverted): array {
        // Array to store reverted data.
        $revertedarray = [];

        // Iterate through the aggregated array.
        foreach ($arrayconverted as $date => $contentarray) {
            foreach ($contentarray as $index => $content) {
                // Add content to the reverted array.
                if (!isset($revertedarray[$index])) {
                    $revertedarray[$index] = ['timecreated' => [], 'content' => []];
                }
                $revertedarray[$index]['timecreated'][] = $date;
                $revertedarray[$index]['content'][] = $content;
            }
        }

        return $revertedarray;
    }


    /**
     * Executes queries to retrieve data for generating charts.
     *
     * @param int|null $userid The ID of the user to retrieve data for. If not provided, the current user is used.
     * @param array $selectedfieldsarray An array containing selected field IDs.
     * @param int|null $selecteddateid The ID of the selected date, if applicable.
     *
     * @return array An array containing the query results.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function executequeries($selectedfieldsarray, $selecteddateid, $userid = null): array {
        global $DB, $USER;
        // If $userid argument is not provided, use the current user.
        $userid = $userid ?? $USER->id;

        // Initialize the results array.
        $results = [];

        foreach ($selectedfieldsarray as $itemid) {

            // Query to select SurveyPro module submissions based on user and specific module item.
            $query = 'SELECT s.id, a.content, s.timecreated, a.itemid
                FROM {surveypro_submission} s
                JOIN {surveypro_answer} a ON a.submissionid = s.id
                WHERE s.status = :status
                AND s.userid = :userid
                AND a.itemid = :itemid';

            // Execute the query for each item ID in the array.
            $queryparams = ['status' => 0, 'userid' => $userid, 'itemid' => (int)$itemid];
            $result = $DB->get_records_sql($query, $queryparams);

            // Check if the result is not empty and has the expected structure.
            if (!empty($result) && isset($result)) {
                foreach ($result as $item) {
                    $value = $item->content;
                    $id = $item->id;
                    $itemid = $item->itemid;

                    if (empty($value)) {
                        $value = 0;
                    }

                    if ($value !== '@@_NOANSW_@@' && is_numeric($value) && $value >= 0) {
                        $date = 0;

                        if (!empty($selecteddateid)) {
                            $querydate = 'SELECT a.content
                                FROM {surveypro_submission} s
                                JOIN {surveypro_answer} a ON a.submissionid = s.id
                                WHERE a.itemid = :itemid
                                AND s.id = :submissionid';

                            $resultdate = $DB->get_record_sql($querydate, ['itemid' => $selecteddateid, 'submissionid' => $id]);

                            if ($resultdate->content === '@@_NOANSW_@@') {
                                $date = 0;
                            } else {
                                $date = $resultdate->content;
                            }
                        } else {
                            $date = $item->timecreated;
                        }

                        // Create an associative array with 'id', 'content', 'timecreated', and 'itemid'.
                        $resultwithfirstdatendid = ['id' => $id, 'content' => $value, 'timecreated' => $date, 'itemid' => $itemid];
                        // Check if the value to search for is present.
                        array_push($results, $resultwithfirstdatendid);
                    }
                }
            }
        }
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
    public function rendermustachefile($pathfile, $data) {
        if (file_exists($pathfile)) {
            // Create a new Mustache engine and load the template file.
            $mustache = new \Mustache_Engine();
            $template = file_get_contents($pathfile);
            // Ensure $data is an array before sanitizing.
            if (is_array($data)) {
                // Sanitize each element of the data array.
                $sanitizeddata = array_map(function ($value) {
                    return is_string($value) ? htmlspecialchars($value) : $value;
                }, $data);
                // Render the template with the sanitized data object.
                echo $mustache->render($template, $sanitizeddata);
            } else {
                echo "Invalid data type. Expected an array.";
            }
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
     * @param array $variablesarray An array containing variable names.
     * @param array $selectedfieldsarray An array containing selected field IDs.
     * @param int $userid Optional; The ID of the user to generate the chart for. Defaults to the current user.
     *
     * @return echo html render The HTML output for the user's chart and mergedarray.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function singleuserchart($message, $title, $variablesarray, $selectedfieldsarray, $selecteddateid, $userid = null) {
        global $USER;
        // Set the user ID to the current user if not specified.
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        $arrayconvertedarray = $this->executequeries($selectedfieldsarray, $selecteddateid, $userid);

        $transformedarray = $this->transformarray($arrayconvertedarray);

        // If the user has not completed the survey, display a message.
        if (empty($transformedarray)) {
            echo "<br><br>";
            echo \html_writer::tag('h5 class="padding-top-bottom"', $message);
        } else {
            // Generate the chart with the specified title.
            echo \html_writer::tag(
                'div class="padding-top-bottom"',
                $this->generatechart(
                    $variablesarray,
                    $transformedarray,
                    $title
                )
            );
        }
    }

    /**
     * This function takes an array of arrays ($arrayconvertedarray) and creates a merged array
     * that contains all the fields needed for each record.
     *
     * @param array $variablesarray An array of arrays containing names of different variables.
     * @param array $arrayconvertedarray An array of arrays containing data for different variables.
     * @return array The merged array containing all the fields needed for each record.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function createmergedarray($variablesarray, $transformedarray) {
        // Create an empty array to hold the merged data.

        // Convert the input array into the required format for chart generation.
        $arrayconverted = $this->convertarray($variablesarray, $transformedarray);

        // Revert the converted array back to the original format.
        $arrayreverted = $this->revertarray($arrayconverted);

        $mergedarray = [];
        // Get the length of any of the arrays (assuming they all have the same length).
        $lengthdata = count($arrayreverted[0]['content']);
        $lengthvar = count($variablesarray);
        // Iterate through all arrays at once.
        for ($k = 0; $k < $lengthdata; $k++) {
            // Create a new array containing the current elements from all arrays in $transformedarray.
            $elementarray = [];
            // Add the current timecreated element to the new array.
            $elementarray[] = $arrayreverted[0]['timecreated'][$k];
            for ($j = 0; $j < $lengthvar; $j++) {
                // Add the current content element for each variable to the new array.
                $elementarray[] = $arrayreverted[$j]['content'][$k];
            }
            // Add the new array to the merged array.
            array_push($mergedarray, $elementarray);
        }

        // Return the merged array.
        return $mergedarray;
    }

    /**
     * Writes data to a CSV file.
     *
     * @param string $date The date of the measurement.
     * @param string $filename The name of the file to write to.
     * @param string $delimiter The delimiter to use in the CSV file.
     * @param array $variablesarray An array containing variable names.
     * @param array $mergedarray The merged array of data to write to the CSV file.
     *
     * @return void
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function writingfile($filename, $delimiter, $variablesarray, $mergedarray) {
        global $CFG;

        // Open the file for writing.
        $exportsubdir = 'mod_spromonitor/csv';
        make_temp_directory($exportsubdir);
        $filewithpath = $CFG->tempdir . '/' . $exportsubdir . '/' . $filename;
        $filehandler = fopen($filewithpath, 'w');
        // Check if the file was opened successfully.
        if ($filehandler) {
            // Initialize an array to store the maximum width of each column.
            $columnwidths = [];
            // Create an array of the header row.
            $headerrow = [];
            // Add variable names to the header row and initialize column widths.
            foreach ($variablesarray as $variable) {
                $headerrow[] = $variable;
                // Initialize column width based on the length of the variable name.
                $columnwidths[$variable] = strlen($variable);
            }
            $csv = [$headerrow];
            // Loop through the merged array and create a new array for each row of data.
            foreach ($mergedarray as $elementarray) {
                $newelement = [$headerrow[0] => $elementarray[0]];
                // Add content values to the new array and update column widths.
                foreach ($elementarray as $index => $content) {
                    // Skip the first element, as it represents the timecreated.
                    if ($index > 0 && isset($variablesarray[$index])) {
                        $variable = $variablesarray[$index];
                        $newelement[$variable] = $content;
                        // Update column width if needed.
                        $columnwidths[$variable] = max($columnwidths[$variable], strlen($content));
                    }
                }
                array_push($csv, $newelement);
            }
            // Write the header to the CSV file.
            fputcsv($filehandler, array_combine($headerrow, $headerrow), $delimiter);
            // Loop through the data and write each row to the CSV file.
            foreach ($csv as $index => $row) {
                // Skip the padding for the first row (headers).
                if ($index > 0) {
                    // Pad each column value to match the maximum width of the column.
                    foreach ($row as $variable => $content) {
                        $row[$variable] = str_pad($content, (float)$columnwidths[$variable]);
                    }
                    fputcsv($filehandler, $row, $delimiter);
                }
            }
            fclose($filehandler);
        }
    }

    /**
     * Assigns the value of $courseid based on the GET, POST, and session variables.
     *
     * @return int The value of $courseid assigned based on the GET, POST, and session variables.
     */
    public function getcourseid() {
        // Check if the value is present in the GET variables.
        $courseid = optional_param('courseid', 0, PARAM_INT);
        if ($courseid == 0) {
            $courseid = optional_param('context_id', 0, PARAM_INT);
        }
        return $courseid;
    }

    /**
     * Function to handle the conversion of $selectedfields into an array by splitting values based on commas.
     *
     * @param mixed $fields The fields to handle.
     *
     * @return array The array resulting from handling the fields.
     */
    public function handleselectedfields($fields) {
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
     * @param array $variablesarray An array containing variable names.
     * @param array $mergedarray A merged array containing all the fields needed for each record.
     *
     * @return string The generated file name.
     */
    public function generatefilename($username, $datestring, $variablesarray, $mergedarray) {
        global $CFG;
        $delimiter = ';';
        $date = userdate(time(), '%d%m%Y', 99, false, false); // Gets a formatted date as a string.
        // Add datestring as the first element of variablesarray.
        array_unshift($variablesarray, $datestring);
        // Create an array with continuous numerical keys.
        $variablesarray = array_values($variablesarray);
        $filename = 'file_' . $date . '_' . $username . '.csv'; // Creates the file name.
        $filepath = $CFG->dirroot . '/mod/spromonitor/' . $filename; // Creates the full file path.
        // Calls the writingFile() function to write the CSV file.
        // The source code for writingFile() is not included in this description.
        $this->writingfile(
            $filename,
            $delimiter,
            $variablesarray,
            $mergedarray
        );
        return $filename; // Returns the generated file name.
    }

    /**
     * Transforms an array of chart data into a structured format suitable for rendering a chart.
     *
     * @param array $arrayconvertedarray An array containing chart data, each element having keys
     * like 'itemid', 'timecreated', and 'content'.
     * @return array The transformed array structure, where each item is represented with 'timecreated' and 'content' arrays.
     */
    public function transformarray($arrayconvertedarray) {
        // Initialize an empty array to store the transformed data.
        $transformedarray = [];

        // Loop through each element in the input chart data array.
        foreach ($arrayconvertedarray as $data) {
            // Extract relevant data from the current element.
            $itemid = $data['itemid'];
            $timecreated = date('d/m/Y', (int)$data['timecreated']); // Convert the timestamp to a readable date.

            // If the item ID is not yet present in the transformed array, initialize it with empty arrays.
            if (!isset($transformedarray[$itemid])) {
                $transformedarray[$itemid] = [
                    'timecreated' => [],
                    'content' => [],
                ];
            }

            // Append the time created and content to their respective arrays for the current item.
            $transformedarray[$itemid]['timecreated'][] = $timecreated;
            $transformedarray[$itemid]['content'][] = $data['content'];
        }

        // Reset array keys to ensure a numerically indexed array.
        return array_values($transformedarray);
    }
}
