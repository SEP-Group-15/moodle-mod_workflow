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

use mod_workflow\form\validate;
use mod_workflow\request;
use mod_workflow\workflow;
use mod_workflow\message_handler;

require_once(__DIR__ . '/../../config.php');
require_login();

global $DB;

$id = optional_param('id', null, PARAM_INT);
$edit = optional_param('edit', true, PARAM_BOOL);
$cmid = optional_param('cmid', true, PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'workflow');
$context = context_module::instance($cm->id);

$PAGE->set_url(new moodle_url('/mod/workflow/validate.php'));
$PAGE->set_context($context);
$request_manager = new request();
$activityname = $request_manager->getActivityName($id);
$PAGE->set_heading('Validate Request - ' . $activityname);
$PAGE->set_title('Validate request');
$PAGE->navbar->add('Validate Request');
$PAGE->set_cm($cm, $course);

$mform = new validate();
$msg_handler = new message_handler();

if ($mform->is_cancelled()) {
    //go back to manage page
    redirect($CFG->wwwroot . '/mod/workflow/view.php?id=' . $cmid, 'Validation is Cancelled');
} else if ($fromform = $mform->get_data()) {
    $request_manager = new request();
    $workflow = new workflow();
    $validity['0'] = "valid";
    $validity['1'] = "rejected";
    $request_manager->validate(
        $fromform->id,
        $validity[$fromform->validity],
        $fromform->instructor_comment
    );
    $workflowid = $request_manager->getRequest($fromform->id)->workflowid;
    $workflow_curr = $workflow->getWorkflow($workflowid);
    $lec_msg = $USER->firstname . ' ' . $USER->lastname . ' has validated a request of ID: ' . $fromform->id . ' as ' . $validity[$fromform->validity] . '.';
    $msg_handler->send($fromform->studentid, 'Your request of ID:' . $fromform->id . ' is validated as ' . ucwords($validity[$fromform->validity]), $cmid);
    if ($validity[$fromform->validity] == "valid") {
        $msg_handler->send($workflow_curr->lecturerid, $lec_msg, $cmid);
    }
    redirect($CFG->wwwroot . '/mod/workflow/view.php?id=' . $fromform->cmid, 'Request is ' . $validity[$fromform->validity]);
}

if ($id) {
    $types = [
        "Deadline extension" => '0',
        "Failure to attempt" => '1',
        "Late submission" => '2',
        "Other" => '3',
    ];
    $request_manager = new request();
    $request = $request_manager->getRequest($id);
    $request->type = $types[$request->type];
    $request->cmid = $cmid;
    if (!$request) {
        die("Request");
        \core\notification::add('Request not found', \core\output\notification::NOTIFY_WARNING);
    }
    $mform->set_data($request);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
