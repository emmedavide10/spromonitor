<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package     mod_spromonitor
 * @copyright   2024 onwards kordan <stringapiccola@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function spromonitor_supports(string $feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_OTHER;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_spromonitor into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $spromonitor An object from the form.
 * @param mod_spromonitor_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function spromonitor_add_instance($spromonitor, $mform = null) {
    global $DB;

    $spromonitor->timecreated = time();

    $id = $DB->insert_record('spromonitor', $spromonitor);

    return $id;
}

/**
 * Updates an instance of the mod_spromonitor in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $spromonitor An object from the form in mod_form.php.
 * @param mod_spromonitor_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function spromonitor_update_instance($spromonitor, $mform = null) {
    global $DB;

    $spromonitor->id = $spromonitor->instance;
    $spromonitor->timemodified = time();

    return $DB->update_record('spromonitor', $spromonitor);
}

/**
 * Removes an instance of the mod_spromonitor from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function spromonitor_delete_instance(int $id) {
    global $DB;

    $exists = $DB->get_record('spromonitor', ['id' => $id]);
    if ($exists) {
        $DB->delete_records('spromonitor', ['id' => $id]);
        $return = true;
    } else {
        $return = false;
    }

    return $return;
}

/**
 * Extends the global navigation tree by adding mod_spromonitor nodes if there is a relevant content.
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $spromonitornode An object representing the navigation tree node.
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function spromonitor_extend_navigation(navigation_node $spromonitornode, stdClass $course, stdClass $spromonitor, cm_info $cm) {

}

/**
 * Extends the settings navigation with the mod_spromonitor settings.
 *
 * This function is called when the context for the page is a mod_spromonitor module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@see settings_navigation}
 * @param navigation_node $spromonitornode {@see navigation_node}
 */
function spromonitor_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $spromonitornode = null) {

}

