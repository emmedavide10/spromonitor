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
 * @copyright  2024 Davide Mirra <davide.mirra@iss.it>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_monitoring;

class utility
{

    
    public function bckTable(){
        global $CFG, $DB;

        $table = 'tool_monitoring';
        $filename = $table . date('Y_m_d') . '.sql';
        $filepath = $CFG->dirroot . '\\admin\\tool\\monitoring\\' . $filename; // Creates the full file path.

        $file_bck = fopen($filepath, 'w');

        $rows = $DB->get_records($table);
        foreach($rows as $row){
            $sql = "INSERT INTO $table (id, courseid, surveyproid, fieldscsv, measuredateid, timecreated, timemodified)
            VALUES ($row->id, $row->courseid, $row->surveyproid, $row->fieldscsv, $row->measuredateid, $row->timecreated, $row->timemodified);\n";
            fwrite($file_bck, $sql);
        }
        fclose($file_bck);
    }
    



    /**
     * Generates a line chart based on input data arrays.
     *
     * @param string $title Optional; The chart title.
     * @param array $variablesarray An array containing variable names.
     * @param array $chartdataarray An array containing chart data arrays with 'content' and 'timecreated' keys.
     *
     * @return string HTML containing the generated chart.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */

    public function generatechart($variablesarray, $transformedarray, $title = ''): string
    {

        global $OUTPUT;
        // Create chart series for each data array.
        $chartseries = [];
        foreach ($variablesarray as $index => $variable) {
            $contentarray = $transformedarray[$index]['content'];
            $timecreatedarray = $transformedarray[$index]['timecreated'];

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
     * Executes queries to retrieve data for generating charts.
     *
     * @param int|null $userid The ID of the user to retrieve data for. If not provided, the current user is used.
     * @param array $selectedfieldsarray An array containing selected field IDs.
     *
     * @return array An array containing the query results.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function executequeries($selectedfieldsarray, $selecteddateid, $userid = null): array
    {
        global $DB, $USER;
        // If $userid argument is not provided, use the current user.
        $userid = $userid ?? $USER->id;

        // Initialize the results array.
        $results = array();

        foreach ($selectedfieldsarray as $itemid) {

            // Query to select SurveyPro module submissions based on user and specific module item.
            $query = 'SELECT s.id, a.content, s.timecreated, a.itemid
            FROM {surveypro_submission} s
            JOIN {surveypro_answer} a ON a.submissionid = s.id
            WHERE s.status = :status
            AND s.userid = :userid
            AND a.itemid = :itemid';

            // Esegui la query per ogni item ID nell'array.
            $queryparam = ['status' => 0, 'userid' => $userid, 'itemid' => (int)$itemid];
            $result = $DB->get_records_sql($query, $queryparam);

            // Check se il risultato non è vuoto e ha la struttura attesa.
            if (!empty($result) && isset($result)) {
                foreach ($result as $item) {
                    $value = $item->content;
                    $id = $item->id;
                    $itemid = $item->itemid;

                    if ($value != '@@_NOANSW_@@' && is_numeric($value) && $value >= 0) {

                        $date = '';

                        if (!empty($selecteddateid)) {
                            $querydate = 'SELECT a.content
                            FROM {surveypro_submission} s
                            JOIN {surveypro_answer} a ON a.submissionid = s.id
                            WHERE a.itemid = :itemid
                            AND s.id = :submissionid';

                            $resultdate = $DB->get_record_sql($querydate, ['itemid' => $selecteddateid, 'submissionid' => $id]);
                            $date = $resultdate->content;
                        } else {
                            $date = $item->timecreated;
                        }

                        // Crea un array associativo con content e timecreated.
                        $resultWithDateAndId = array('id' => $id, 'content' => $value, 'timecreated' => $date, 'itemid' => $itemid);
                        // Verifica se il valore da cercare è presente
                        array_push($results, $resultWithDateAndId);
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
    public function rendermustachefile($pathfile, $data)
    {
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
    public function singleuserchart($message, $title, $variablesarray, $selectedfieldsarray, $selecteddateid, $userid = null)
    {
        global $USER;
        // Set the user ID to the current user if not specified.
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        $chartdataarray = $this->executequeries($selectedfieldsarray, $selecteddateid, $userid);
        $transformedarray = $this->transformarray($chartdataarray);

        // If the user has not completed the survey, display a message.
        if (isset($transformedarray)) {
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
     * This function takes an array of arrays ($chartdataarray) and creates a merged array
     * that contains all the fields needed for each record.
     *
     * @param array $variablesarray An array of arrays containing names of different variables.
     * @param array $chartdataarray An array of arrays containing data for different variables.
     * @return array The merged array containing all the fields needed for each record.
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function createmergedarray($variablesarray, $transformedarray)
    {
        // Create an empty array to hold the merged data.

        $mergedarray = [];
        // Get the length of any of the arrays (assuming they all have the same length).
        $lengthdata = count($transformedarray[0]['content']);
        $lengthvar = count($variablesarray);
        // Iterate through all arrays at once.
        for ($k = 0; $k < $lengthdata; $k++) {
            // Create a new array containing the current elements from all arrays in $transformedarray.
            $elementarray = [];
            // Add the current timecreated element to the new array.
            $elementarray[] = $transformedarray[0]['timecreated'][$k];
            for ($j = 0; $j < $lengthvar; $j++) {
                // Add the current content element for each variable to the new array.
                $elementarray[] = $transformedarray[$j]['content'][$k];
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
    public function writingfile($filename, $delimiter, $variablesarray, $mergedarray)
    {
        global $CFG;
        // Open the file for writing.
        $exportsubdir = 'tool_monitoring/csv';
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
    public function getcourseid()
    {
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
    public function handleselectedfields($fields)
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
     * @param array $variablesarray An array containing variable names.
     * @param array $mergedarray A merged array containing all the fields needed for each record.
     *
     * @return string The generated file name.
     */
    public function generatefilename($username, $datestring, $variablesarray, $mergedarray)
    {
        global $CFG;
        $delimiter = ';';
        $date = userdate(time(), '%d%m%Y', 99, false, false); // Gets a formatted date as a string.
        // Add datestring as the first element of variablesarray.
        array_unshift($variablesarray, $datestring);
        // Create an array with continuous numerical keys.
        $variablesarray = array_values($variablesarray);
        $filename = 'file_' . $date . '_' . $username . '.csv'; // Creates the file name.
        $filepath = $CFG->dirroot . '/admin/tool/monitoring/' . $filename; // Creates the full file path.
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


    function transformarray($chartDataArray)
    {
        $transformedarray = [];

        foreach ($chartDataArray as $data) {
            $itemId = $data['itemid'];
            $timecreated = date('d/m/Y', $data['timecreated']); // Converte il timestamp in una data leggibile

            if (!isset($transformedarray[$itemId])) {
                $transformedarray[$itemId] = [
                    'timecreated' => [],
                    'content' => [],
                ];
            }

            $transformedarray[$itemId]['timecreated'][] = $timecreated;
            $transformedarray[$itemId]['content'][] = $data['content'];
        }

        return array_values($transformedarray);
    }
}
