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

class approve extends moodleform
{
    public function definition()
    {
        global $DB;
        $requestid = required_param('id', PARAM_INT);
        $request = $DB->get_record('request', ['id' => $requestid]);

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $elem_request1 = $mform->addElement('textarea', 'studentid', "Student ID", 'wrap="virtual" rows="1" cols="50"');
        $mform->setDefault('studentid', "");

        $elem_request = $mform->addElement('textarea', 'request', "Request", 'wrap="virtual" rows="5" cols="50"');
        $mform->setDefault('request', "Enter your request");

        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'isbatchrequest', '', 'Individual', 0);
        $radioarray[] = $mform->createElement('radio', 'isbatchrequest', '', 'Batch', 1);
        $elem_radio = $mform->addGroup($radioarray, 'isbatchrequest', 'Individual/Batch request', array(' '), false);

        if ($request->activityid !== '') {
            $types = array();
            $types['0'] = "Deadline extension";
            $types['1'] = "Failure to attempt";
            $types['2'] = "Late submission";
            $types['3'] = "Other";

            $elem_type = $mform->addElement('select', 'type', 'Select type', $types);
            $mform->setDefault('type', 0);
            $elem_type->freeze();
        }

        if ($request->filename != '') {
            $link = '/moodle/mod/workflow/uploads/' . $request->files . '/' . $request->filename;
            $html = '<a class="" href="' . $link . '" download = "' . $link . '">' . $request->filename . '</a>';
        } else {
            $html = 'None';
        }
        $mform->addElement('static', 'File submission', 'File submission', $html);

        $workflowid = $request->workflowid;
        $workflow =  $DB->get_record('workflow', ['id' => $workflowid]);

        if ($workflow->instructorid !== '0') {
            $elem_instructor_comment = $mform->addElement('textarea', 'instructorcomment', "Comments by instructor", 'wrap="virtual" rows="5" cols="50"');
            $mform->setDefault('instructorcomment', "Enter comments regarding request");
            $elem_instructor_comment->freeze();
        }

        if ($request->activityid === '') {
            if ($workflow->instructorid !== '0') {
                $validity = array();
                $validity['0'] = "Valid";
                $validity['1'] = "Reject";

                $elem_validty = $mform->addElement('select', 'validity', 'Validity', $validity);
                $mform->setDefault('validity', 0);
                $elem_validty->freeze();
            }
            $elem_lec_comment = $mform->addElement('textarea', 'lec_comment', "Feedback", 'wrap="virtual" rows="5" cols="50"');
            $mform->setDefault('lec_comment', "");
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', "Submit");
            $buttonarray[] = $mform->createElement('cancel');
            $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);

            $mform->addElement('hidden', 'approval');
            $mform->setType('approval', PARAM_INT);
            $mform->setDefault('approval', 0);
        } else {
            if ($workflow->instructorid !== '0') {
                $validity = array();
                $validity['0'] = "Valid";
                $validity['1'] = "Reject";

                $elem_validty = $mform->addElement('select', 'validity', 'Validity', $validity);
                $mform->setDefault('validity', 0);
                $elem_validty->freeze();
            }

            $elem_lec_comment = $mform->addElement('textarea', 'lec_comment', "Feedback", 'wrap="virtual" rows="5" cols="50"');
            $mform->setDefault('lec_comment', "");

            $mform->addElement('date_time_selector', 'extended_date', "Extend due date to");

            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', "Submit");
            $buttonarray[] = $mform->createElement('cancel');

            $validity = array();
            $validity['0'] = "Approve";
            $validity['1'] = "Reject";

            $elem_approval = $mform->addElement('select', 'approval', '', $validity);
            $mform->setDefault('approval', 0);
            $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
        }

        $elem_request1->freeze();
        $elem_request->freeze();
        $elem_radio->freeze();
    }
}
