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
 * Define the complete assignment structure for restore, with file and id annotations
 *
 * @package    mod_spromonitor
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Include the required mod spromonitor upgrade code.
require_once(dirname(__FILE__).'/../../../../config.php');
require_once($CFG->dirroot.'/mod/spromonitor/db/upgradelib.php');

/**
 * Define all the restore steps that will be used by the restore_spromonitor_activity_task
 *
 * @package   mod_spromonitor
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_spromonitor_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the restore workflow.
     *
     * @return void
     */
    protected function define_structure() {

        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('spromonitor', '/activity/spromonitor');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a spromonitor restore.
     *
     * @param object $data Data in object form
     * @return void
     */
    protected function process_spromonitor($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $moduleversion = $this->task->get_old_moduleversion();

        // Old backups using advanced field map to new reserved field.
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the spromonitor record.
        $newitemid = $DB->insert_record('spromonitor', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Once the database tables have been fully restored, restore the files.
     *
     * @return void
     */
    protected function after_execute() {
        global $DB;

        // Add spromonitor related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_spromonitor', 'intro', null);
    }

    /**
     * Hook to execute spromonitor upgrade after restore.
     */
    protected function after_restore() {
        global $DB;

        // Get the id of this spromonitor.
        $spromonitorid = $this->task->get_activityid();

        $spromonitor = $DB->get_record('spromonitor', ['id' => $spromonitorid], '*', MUST_EXIST);
    }
}
