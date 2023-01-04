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
 * Version details
 *
 * @package    mod_workflow
 * @copyright  2022 SEP15
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_workflow\form;

use moodleform;

require_once("$CFG->libdir/formslib.php");

use mod_workflow\workflow;

class create extends moodleform
{
    public function definition()
    {
        global $SESSION, $USER, $DB;

        $mform = $this->_form;

        $workflow = new workflow();
        $representativeid = $workflow->getRepresentativeId($SESSION->workflowid);
        $temp_workflow = $workflow->getWorkflow($SESSION->workflowid);

        [$course, $cm] = get_course_and_cm_from_cmid(optional_param('cmid', true, PARAM_INT), 'workflow');

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'workflowid');
        $mform->setType('workflowid', PARAM_INT);
        $mform->setDefault('workflowid', $SESSION->workflowid);

        $mform->addElement('textarea', 'request', "Request", 'wrap="virtual" rows="5" cols="50"');
        $mform->setDefault('request', '');
        $mform->addRule('request', null, 'required', null, 'client');

        if ($USER->id === $representativeid) {
            $radioarray = array();
            $radioarray[] = $mform->createElement('radio', 'isbatchrequest', '', 'Individual', 0);
            $radioarray[] = $mform->createElement('radio', 'isbatchrequest', '', 'Batch', 1);
            $mform->addGroup($radioarray, 'isbatchrequest', 'Individual/Batch request', array(' '), false);
        } else {
            $mform->addElement('hidden', 'isbatchrequest');
            $mform->setType('isbatchrequest', PARAM_INT);
            $mform->setDefault('isbatchrequest', '0');
        }

        if ($temp_workflow->type == 'general') {
            $quizzes = $DB->get_records_select('quiz', 'course = ' . $course->id);
            $assignments = $DB->get_records_select('assign', 'course = ' . $course->id);
            $activities[null] = 'None';
            foreach ($quizzes as $quiz) {
                $activities['q' . $quiz->id] = $quiz->name;
            }
            foreach ($assignments as $assignment) {
                $activities['a' . $assignment->id] = $assignment->name;
            }
            $mform->addElement('select', 'activityid', 'Activity', $activities);
            $mform->setDefault('activityid', null);
        } else {
            $mform->addElement('hidden', 'activityid');
            $mform->setDefault('activityid', $temp_workflow->activityid);
            if ($temp_workflow->activityid[0] == 'q') {
                $types = array();
                $types['1'] = "Failure to attempt";
                $types['3'] = "Other";
            } else if ($temp_workflow->activityid[0] == 'a') {
                $types = array();
                $types['0'] = "Deadline extension";
                $types['1'] = "Failure to attempt";
                $types['2'] = "Late submission";
                $types['3'] = "Other";
            }

            $mform->addElement('select', 'type', 'Select type', $types);
            $mform->setDefault('type', null);
            $mform->hideIf('type', 'activityid', 'eq', null);
        }

        if ($temp_workflow->filesallowed) {
            $mform->addElement(
                'filepicker',
                'files',
                'File submission',
                null,
                array('subdirs' => 1, 'maxfiles' => 1, 'accepted_types' => '*')
            );
        } else {
            $mform->addElement('hidden', 'files');
            $mform->setType('files', PARAM_INT);
            $mform->setDefault('files', '0');
        }

        $this->add_action_buttons();
    }
}
