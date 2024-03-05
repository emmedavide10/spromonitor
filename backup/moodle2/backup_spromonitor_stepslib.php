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

/**
 * Define the complete spromonitor structure for backup, with file and id annotations
 *
 * @package   mod_spromonitor
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_spromonitor_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure for the assign activity.
     *
     * @return void
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        // Root element describing spromonitor instance.
        $spromonitor = new backup_nested_element(
                'spromonitor', ['id'], [
                    'name', 'intro', 'introformat',
                    'surveyproid', 'fieldscsv', 'measuredateid',
                    'timecreated', 'timemodified',
                ]
             );

        // Define sources.
        $spromonitor->set_source_table('spromonitor', ['id' => backup::VAR_ACTIVITYID]);

        // Return the root element (spromonitor), wrapped into standard activity structure.
        return $this->prepare_activity_structure($spromonitor);
    }
}
