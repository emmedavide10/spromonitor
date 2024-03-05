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
 * @package    spromonitor
 * @copyright  2024 Davide Mirra <davide.mirra@iss.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Include necessary Moodle files.
require_once(__DIR__.'/../../config.php');

// Check user access or require_course_login(), require_admin(), depending on the requirements..
require_login();

// Ensure the script is only accessed within Moodle.
defined('MOODLE_INTERNAL') || die();

// Define constant for the CSV path within the spromonitor directory.
define('SPROMONITOR_CSV_PATH', '/mod_spromonitor/csv/');

// Instantiate the utility class.
$utility = new \mod_spromonitor\utility();


// Get the requested file name from parameters.
$filename = required_param('f', PARAM_TEXT);

// Sanitize the filename to prevent XSS.
$filename = clean_param($filename, PARAM_FILE);

$filenotexist = get_string('filenotexist', 'spromonitor');

// Check if the requested file exists.
if (file_exists($CFG->tempdir . SPROMONITOR_CSV_PATH . $filename)) {
    // Set HTTP headers for file download.
    header("Content-Type: application/download\n");
    header("Content-Disposition: attachment; filename=\"" . rawurlencode($filename) . "\"");
    header('Expires: 0');
    header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
    header('Pragma: public');

    // Open and output the file content.
    $exportfilehandler = fopen($CFG->tempdir . SPROMONITOR_CSV_PATH . $filename, 'rb');
    print fread($exportfilehandler, filesize($CFG->tempdir . SPROMONITOR_CSV_PATH . $filename));
    fclose($exportfilehandler);
} else {
    // If the file does not exist, display an error message.
    $data = [
        'filenotexist' => $filenotexist,
    ];
    $utility->rendermustachefile('templates/templateerrorfile.mustache', $data);
}
