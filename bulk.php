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

use mod_workflow\form\bulk_approve;
use mod_workflow\request;
use mod_workflow\message_handler;

require_once(__DIR__ . '/../../config.php'); // setup moodle
require_login();

global $DB;

$edit = optional_param('edit', true, PARAM_BOOL);
$cmid = optional_param('cmid', true, PARAM_INT);
$workflowid = optional_param('workflowid', null, PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'workflow');
$context = context_module::instance($cm->id);


$PAGE->set_url(new moodle_url('/mod/workflow/bulk.php'));
$PAGE->set_context($context);
$PAGE->set_title('Approve bulk request');
$PAGE->set_heading('Approve Bulk Requests');
$PAGE->navbar->add('Approve Bulk Request');
$PAGE->set_cm($cm, $course);

//$mform = new bulk_approve();

$request_ids = array();
if (isset($_POST)) {
    $timestamp = strtotime($_POST['date']);
    $validity = $_POST['validity'];
    foreach ($_POST as $elem => $sel) {
        if (strpos($elem, 'requestid') === 0) {
            $request_ids[] = $sel;
        }
    }
}

if(empty($request_ids)) {
    redirect($CFG->wwwroot . '/mod/workflow/view.php?id=' . $cmid, 'Select requests', null, \core\output\notification::NOTIFY_ERROR);
}

$request_manager = new request();
$msg_handler = new message_handler();

foreach ($request_ids as $id) {
    $request_manager->finalizeRequest($id, $validity, $timestamp, '');
    $request= $request_manager->getRequest($id);
    $msg_handler->send($request->studentid, 'Your request ID:' . $id . ' is ' . ucwords($request->status), $cmid);
}
$status = array();
$status['0'] = "approved";
$status['1'] = "rejected";

redirect($CFG->wwwroot . '/mod/workflow/view.php?id=' . $cmid, 'Requests are '.$status[$validity]);

 echo $OUTPUT->header();
 $mform->display();
 echo $OUTPUT->footer();
