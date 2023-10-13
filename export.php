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
 * Export data chart.
 *
 * @copyright  2023 Davide Mirra <davide.mirra@iss.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_monitoring;
use Config;

require_once __DIR__ . '/../../../config.php';

defined('MOODLE_INTERNAL') || die();
define('MONITORING_CSV_PATH', '/tool_monitoring/csv/');

require_login();

$filename = required_param('f', PARAM_TEXT);

if (file_exists($CFG->tempdir.MONITORING_CSV_PATH.$filename)) {
    header("Content-Type: application/download\n");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Expires: 0');
    header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
    header('Pragma: public');

    $exportfilehandler = fopen($CFG->tempdir.MONITORING_CSV_PATH.$filename, 'rb');
    print fread($exportfilehandler, filesize($CFG->tempdir.MONITORING_CSV_PATH.$filename));
    fclose($exportfilehandler);
} else {
      echo 'The requested file does\'t exist';
}
