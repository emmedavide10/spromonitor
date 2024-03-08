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
 * The main mod_spromonitor configuration form.
 *
 * @package     mod_spromonitor
 * @copyright   2024 onwards kordan <stringapiccola@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_spromonitor
 * @copyright   2013 onwards kordan <stringapiccola@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_spromonitor_mod_form extends moodleform_mod
{
    /**
     * Defines form elements
     */
    public function definition()
    {
        global $CFG, $COURSE, $DB;

        // Check if the form, with a surveyproid onboard, was reloaded.
        $surveyproid = optional_param('surveyproid', 0, PARAM_INT);

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $fieldname = 'general';
        $mform->addElement('header', $fieldname, get_string($fieldname, 'form'));

        // Adding the standard "name" field.
        $fieldname = 'name';
        $mform->addElement('text', $fieldname, get_string('spromonitorname', 'mod_spromonitor'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType($fieldname, PARAM_TEXT);
        } else {
            $mform->setType($fieldname, PARAM_CLEANHTML);
        }
        $mform->addRule($fieldname, get_string('required'), 'required', null, 'client');
        $mform->addRule($fieldname, get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton($fieldname, 'surveyproname', 'surveypro');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Adding a fieldset for spromonitor settings.
        $fieldname = 'modulesettinghdr';
        $mform->addElement('header', $fieldname, get_string($fieldname, 'mod_spromonitor'));

        // Adding a select to get the linked surveypro.
        $fieldname = 'surveyproid';
        $surveysnames = $DB->get_records_menu('surveypro', ['course' => $COURSE->id], 'name', 'id, name');
        $mform->registerNoSubmitButton('reload');
        $elementgroup = [];
        $elementgroup[] = $mform->createElement('select', $fieldname, get_string($fieldname, 'mod_spromonitor'), $surveysnames);
        $elementgroup[] = $mform->createElement('submit', 'reload', get_string('reload'));
        $mform->addGroup($elementgroup, $fieldname . 'group', get_string('reload'), [' '], false);
        $mform->addHelpButton($fieldname . 'group', $fieldname, 'spromonitor');
        $mform->setType($fieldname, PARAM_INT);
        $mform->_required[] = $fieldname;

        if (!empty($surveyproid) || !empty($this->current->surveyproid)) {
            if (!empty($this->current->surveyproid)) {
                $sproid = $this->current->surveyproid;
            }
            if (!empty($surveyproid)) {
                $sproid = $surveyproid;
            }

            // Adding an autocomplete to choose the numeric fields from the selected surveypro.
            $fieldname = 'fieldscsv';
            $sqlnumeric = 'SELECT i.id, n.variable
                FROM {surveypro_item} i
                JOIN {surveyprofield_numeric} n ON n.itemid = i.id
                WHERE i.surveyproid = :surveyproid
                AND i.plugin = :plugin';

            $sqlparams = ['surveyproid' => $sproid, 'plugin' => 'numeric'];
            $records = $DB->get_records_sql($sqlnumeric, $sqlparams);

            if ($records) {
                $eachnumeric = [];
                foreach ($records as $record) {
                    $eachnumeric[$record->id] = $record->variable;
                }

                $params = [
                    'multiple' => true,
                    'valuehtmlcallback' => function ($value) {
                        global $OUTPUT, $DB;

                        $sqlnumeric = 'SELECT variable
                            FROM {surveyprofield_numeric}
                            WHERE itemid = :itemid';
                        $sqlparams = ['itemid' => $value];
                        $varname = $DB->get_record_sql($sqlnumeric, $sqlparams);
                        // The variable $varname is an stdClass with the property variable if the surveypro item with id = $value.

                        return $OUTPUT->render_from_template('mod_spromonitor/numericfieldslist', $varname);
                    },
                ];
                $mform->addElement('autocomplete', $fieldname, get_string($fieldname, 'mod_spromonitor'), $eachnumeric, $params);
                $mform->setType($fieldname, PARAM_INT);
                $mform->_required[] = $fieldname;

                // Adding a select to choose a date field from the selected surveypro.
                $fieldname = 'measuredateid';
                $sqldate = 'SELECT i.id, d.variable
                    FROM {surveypro_item} i
                    JOIN {surveyprofield_date} d ON d.itemid = i.id
                    WHERE i.surveyproid = :surveyproid
                    AND i.plugin = :plugin';

                $sqlparams = ['surveyproid' => $sproid, 'plugin' => 'date'];
                $records = $DB->get_records_sql($sqldate, $sqlparams);

                if ($records) {
                    $options = [];
                    foreach ($records as $record) {
                        $datefield[$record->id] = $record->variable;
                    }
                    $mform->addElement('select', $fieldname, get_string($fieldname, 'mod_spromonitor'), $datefield);
                } else {
                    $content = \html_writer::start_tag('div', ['class' => 'fitem']);
                    $content .= \html_writer::start_tag(
                        'div',

                        ['class' => 'fstatic fullwidth label_static']
                    );
                    $content .= get_string('missingdate', 'mod_spromonitor');
                    $content .= \html_writer::end_tag('div');
                    $content .= \html_writer::end_tag('div');
                    $mform->addElement('html', $content);
                }
            } else {
                $content = \html_writer::start_tag('div', ['class' => 'fitem']);
                $content .= \html_writer::start_tag('div', ['class' => 'fstatic fullwidth label_static']);
                $content .= get_string('missingnumeric', 'mod_spromonitor');
                $content .= \html_writer::end_tag('div');
                $content .= \html_writer::end_tag('div');
                $mform->addElement('html', $content);
            }
        }

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Allows the module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data)
    {
        parent::data_postprocessing($data);

        if (isset($data->fieldscsv)) {
            $data->fieldscsv = implode(',', $data->fieldscsv);
        }
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array $errors
     */
    public function validation($data, $files)
    {
        global $DB;
    
        $errors = parent::validation($data, $files);
    
        $record = $DB->get_record('spromonitor', ['surveyproid' => $data['surveyproid']]);
    
        if ($data['instance'] != $record->id) {
            $errors['fieldscsv'] = get_string('dubleidnotallowed', 'mod_spromonitor');
        }
    
        if (empty($data['fieldscsv'])) {
            $errors['fieldscsv'] = get_string('missingfieldscsv', 'mod_spromonitor');
        }
    
        return $errors;
    }
    
}
