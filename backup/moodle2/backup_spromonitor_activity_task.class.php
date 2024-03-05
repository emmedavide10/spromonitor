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
 * Define all the backup steps that will be used by the backup_spromonitor_activity_task
 *
 * @package   mod_spromonitor
 * @copyright 2024 onwards kordan <stringapiccola@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/spromonitor/backup/moodle2/backup_spromonitor_stepslib.php'); // Because it exists (must).
require_once($CFG->dirroot.'/mod/spromonitor/backup/moodle2/backup_spromonitor_settingslib.php'); // Because it exists (optional).

/**
 * spromonitor backup task that provides all the settings and steps to perform one complete backup of the activity
 *
 * @package   mod_spromonitor
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_spromonitor_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        // Surveypro only has one structure step.
        $this->add_step(new backup_spromonitor_activity_structure_step('spromonitor_structure', 'spromonitor.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of spromonitors.
        $search = "/(".$base."\/mod\/spromonitor\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SPROMONITORINDEX*$2@$', $content);

        // Link to spromonitor view by moduleid.
        $search = "/(".$base."\/mod\/spromonitor\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SPROMONITORVIEWBYID*$2@$', $content);

        return $content;
    }
}
