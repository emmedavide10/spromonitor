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


namespace tool_monitoring;

// Include necessary Moodle files
require_once __DIR__ . '/../../../config.php';

// Ensure the script is only accessed within Moodle
defined('MOODLE_INTERNAL') || die();

// Define constant for the CSV path within the tool_monitoring directory
define('MONITORING_CSV_PATH', '/tool_monitoring/csv/');

// Instantiate the Utility class
$utility = new Utility();

// Require user login to access this script
require_login();

// Get the requested file name from parameters
$filename = required_param('f', PARAM_TEXT);

$filenotexist = get_string('filenotexist', 'tool_monitoring');

// Check if the requested file exists
if (file_exists($CFG->tempdir . MONITORING_CSV_PATH . $filename)) {
    // Set HTTP headers for file download
    header("Content-Type: application/download\n");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Expires: 0');
    header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
    header('Pragma: public');

    // Open and output the file content
    $exportfilehandler = fopen($CFG->tempdir . MONITORING_CSV_PATH . $filename, 'rb');
    print fread($exportfilehandler, filesize($CFG->tempdir . MONITORING_CSV_PATH . $filename));
    fclose($exportfilehandler);
} else {

    $data = array(
        'filenotexist' => $filenotexist
    );
    // Download csv files.
    $utility->rendermustachefile('templates/templateerrorfile.mustache', $data);
}
