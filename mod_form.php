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
 * The main mod_workflow configuration form.
 *
 * @package     mod_workflow
 * @copyright   2022 SEP15
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/workflow/lib.php');

/**
 * Module instance settings form.
 *
 * @package     mod_workflow
 * @copyright   2022 SEP15
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_workflow_mod_form extends moodleform_mod
{
    public function definition()
    {
        global $DB, $USER;
        $courseid = optional_param('course', false, PARAM_INT);
        if (!$courseid) {
            $id = optional_param('update', true, PARAM_INT);
            [$course, $cm] = get_course_and_cm_from_cmid($id, 'workflow');
            $courseid = $course->id;
        }
        $context = context_course::instance($courseid);

        $mform = $this->_form;

        $mform->addElement('header', 'generalhdr', 'General');
        $mform->setExpanded('generalhdr');

        $mform->addElement('text', 'name', 'Name');
        $mform->setDefault('name', '');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'description', "Description", 'wrap="virtual" rows="5" cols="50"');
        $mform->setDefault('description', '');

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $courseid);

        $mform->addElement('hidden', 'lecturerid');
        $mform->setType('lecturerid', PARAM_INT);
        $mform->setDefault('lecturerid', $USER->id);

        $typearray = array();
        $typearray[] = $mform->createElement('radio', 'type', 'General', null, 'general');
        $typearray[] = $mform->createElement('radio', 'type', 'Activity-related (Assignments, Quizzes)', null, 'activity-related');
        $mform->setDefault('type', 'general');
        $mform->addGroup($typearray, null, 'Type');

        $quizzes = $DB->get_records_select('quiz', 'course = ' . $courseid);
        $assignments = $DB->get_records_select('assign', 'course = ' . $courseid);
        $activities = [];
        foreach ($quizzes as $quiz) {
            $activities['q' . $quiz->id] = $quiz->name;
        }
        foreach ($assignments as $assignment) {
            $activities['a' . $assignment->id] = $assignment->name;
        }
        $mform->addElement('select', 'activityid', 'Activity', $activities);
        $mform->setDefault('activityid', null);
        $mform->hideIf('activityid', 'type', 'eq', 'general');

        $instructorids = $DB->get_fieldset_select('role_assignments', 'userid', 'contextid = :contextid and roleid=:roleid', [
            'contextid' => $context->id,
            'roleid' => '4',
        ]);
        $instructors[null] = 'None';
        foreach ($instructorids as $instructorid) {
            $instructor = $DB->get_record('user', ['id' => $instructorid]);
            $instructors[$instructor->id] = $instructor->firstname . ' ' . $instructor->lastname;
        }
        $mform->addElement('select', 'instructorid', 'Instructor', $instructors);
        $mform->setDefault('instructorid', null);

        $studentids = $DB->get_records_select('role_assignments', 'contextid = ' . $context->id . ' and roleid = 5');
        $students[null] = 'None';
        foreach ($studentids as $studentid) {
            $student = $DB->get_record('user', ['id' => $studentid->userid]);
            $students[$student->id] = $student->idnumber . ' - ' . $student->firstname . ' ' . $student->lastname;
        }
        $mform->addElement('select', 'representativeid', 'Representative', $students);
        $mform->setDefault('representative', null);

        $mform->addElement('advcheckbox', 'filesallowed', 'File submissions', 'Allow');

        $mform->addElement('header', 'availabilityhdr', 'Availability');
        $mform->setExpanded('availabilityhdr');

        $mform->addElement('date_time_selector', 'startdate', 'Start date', array('optional' => true));

        $mform->addElement('date_time_selector', 'enddate', 'Due date', array('optional' => true));

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
