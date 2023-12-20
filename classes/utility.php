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
    public function preparearray($result)
    {
        $content = array();
        $timecreated = array();

        // Itera su ogni riga nel recordset ed estrai i valori di content e timecreated.
        foreach ($result as $row) {
            $content[] = $row->content;
            $timecreated[] = date('d/m/Y', $row->timecreated);
        }

        // Restituisci gli array content e timecreated come un singolo array associativo.
        return array('content' => $content, 'timecreated' => $timecreated);
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
    public function generatechart(
        $title = '',
        $variablesArray,
        $chartDataArrays
    ): string {
        global $OUTPUT;

        // Rimuovi o commenta l'inversione dell'ordine delle variabili.
        //$variablesArray = array_reverse($variablesArray);

        // Create chart series for each data array.
        $chartSeries = [];

        foreach ($variablesArray as $index => $variable) {
            $contentArray = $chartDataArrays[$index]['content'];
            $timecreatedArray = $chartDataArrays[$index]['timecreated'];

            // Create a new chart series for each variable.
            $chartSeries[] = new \core\chart_series($variable, $contentArray);
        }

        //var_dump($chartSeries); die;

        // Create a new line chart and set its properties.
        $chart = new \core\chart_line();
        $chart->set_title($title);
        $chart->set_legend_options(['position' => 'bottom', 'margin-bottom' => 30]);

        // Aggiungi le serie al grafico nell'ordine normale.
        foreach ($chartSeries as $series) {
            $chart->add_series($series);
        }

        // Set the x-axis labels to the date values in the input arrays.
        $chart->set_labels($timecreatedArray);

        // Render the chart using the Moodle output renderer.
        return $OUTPUT->render($chart);
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
    public function executequeries($userid = null, $selectedFieldsArray)
    {
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
     * @param object $utility The chart utility object.
     * @param string $message The message to display if the user has not completed the survey.
     * @param string $title The chart title.
     * @param int $userid Optional; The ID of the user to generate the chart for. Defaults to the current user.
     * @return echo html render The HTML output for the user's chart and mergedarray
     *
     * @since Moodle 3.1
     * @author Davide Mirra
     */
    public function singleuserchart($message, $title, $variablesArray, $selectedFieldsArray, $userid = null)
    {

        global $USER;

        // Set the user ID to the current user if not specified.
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        // Execute the queries to retrieve the chart data.
        $results = $this->executequeries($userid, $selectedFieldsArray);

        $empty = true;
        foreach($results as $subArr) {
          if(count($subArr) != 0) {
            $empty = false;
          }  
        }

        // Prepara gli array dei dati del grafico.
        $chartDataArrays = [];
        foreach ($results as $result) {
            $chartDataArrays[] = $this->preparearray($result);
        }
        // Stampa e termina per vedere i risultati.
        //print_r($chartDataArrays);
        // If the user has not completed the survey, display a message.
        if($empty) {
            echo \html_writer::tag('h5', $message);
        } else {
            // Combine the selected fields into an array

            // Otherwise, generate the chart with the specified title.
            echo \html_writer::tag(
                'div class="padding-top-bottom"',
                $this->generatechart(
                    $title,
                    $variablesArray,
                    $chartDataArrays
                )
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
     * @author Davide Mirra
     */
    public function createmergedarray($variablesArray)
    {
        // Create an empty array to hold the merged data.
        $mergedarray = array();

        // Get the length of any of the arrays (assuming they all have the same length).
        $length = count($variablesArray);

        // Iterate through all arrays at once.
        for ($j = 0; $j < $length; $j++) {
            // Create a new array containing the current elements from all arrays.
            $elementarray = array();

            foreach ($variablesArray as $variableArray) {
                $elementarray[] = $variableArray[$j];
            }

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
    public function writingfile(
        $datestring,
        $variablesArray,
        $filename,
        $delimiter,
        $mergedarray
    ) {
        global $CFG;

        // Open the file for writing.
        $exportsubdir = 'tool_monitoring/csv';
        make_temp_directory($exportsubdir);
        $filewithpath = $CFG->tempdir . '/' . $exportsubdir . '/' . $filename;
        $filehandler = fopen($filewithpath, 'w');

        // Check if the file was opened successfully.
        if ($filehandler) {
            // Create an array of the header row.
            $header = array($datestring);

            // Aggiungi i nomi delle colonne al header.
            $header = array_merge($header, $variablesArray);

            // Scrivi l'header nel file CSV.
            fputcsv($filehandler, $header, $delimiter);

            // Loop attraverso il mergedarray e scrivi ogni riga nel file CSV.
            foreach ($mergedarray as $elementarray) {
                // Creare una nuova riga di dati usando i nomi delle colonne specificati in $variablesArray.
                $rowData = array($elementarray[0]); // Aggiungi la colonna della data.

                // Aggiungi i valori delle colonne specificate in $variablesArray.
                foreach ($variablesArray as $variable) {
                    $rowData[] = $elementarray[$variable];
                }

                // Scrivi la riga nel file CSV.
                fputcsv($filehandler, $rowData, $delimiter);
            }

            fclose($filehandler);
        }
    }



    /**
     * Assegna il valore di $courseid basato sulle variabili GET, POST e di sessione.
     *
     * @return int Il valore di $courseid assegnato basato sulle variabili GET, POST e di sessione.
     */
    public function getCourseId()
    {

        // Controlla se il valore è presente nelle variabili GET
        $courseid = optional_param('courseid', 0, PARAM_INT);
        if ($courseid == 0) {
            $courseid = optional_param('context_id', 0, PARAM_INT);
        }

        return $courseid;
    }



    // Funzione per gestire la conversione di $selectedFields in un array separando i valori in base alle virgole
    public function handleSelectedFields($fields)
    {
        if (!empty($fields)) {
            return is_array($fields) ? $fields : explode(',', $fields);
        }
        return [];
    }


   
    /**
     * Genera il nome del file.
     *
     * @param string $username Il nome utente da utilizzare nel nome del file.
     * @param bool $csv Un flag che indica se è richiesto un file CSV.
     * @param string $datestring Una stringa di data da utilizzare nel nome del file.
     * @param string $weight Una stringa che rappresenta il peso nel file CSV.
     * @param string $waistcircumference Una stringa che rappresenta la circonferenza della vita nel file CSV.
     * @param string $glicemy Una stringa che rappresenta la glicemia nel file CSV.
     *
     * @return string il nome del file generato.
     */
    public function generateFilename($username, $csv, $datestring, $variablesArray, $mergedarray)
    {
        global $CFG;

        $delimiter = ';';

        $date = userdate(time(), '%d%m%Y', 99, false, false); // Ottiene una data formattata come stringa.
        $filename = 'file_' . $date . '_' . $username . '.csv'; // Crea il nome del file.
        $filepath = $CFG->dirroot . '/admin/tool/monitoring/' . $filename; // Crea il percorso completo del file.

        if (isset($csv)) { // Verifica se è richiesto un file CSV.

            // Chiama la funzione writingfile() per scrivere il file CSV (il codice sorgente di writingfile() non è incluso in questa descrizione).
            $this->writingfile(
                $datestring,
                $variablesArray,
                $filename,
                $delimiter,
                $mergedarray
            );
        }

        return $filename; // Restituisce il nome del file generato.
    }

}
