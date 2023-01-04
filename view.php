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
 * Prints an instance of mod_workflow.
 *
 * @package     mod_workflow
 * @copyright   2022 SEP15
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_workflow\request;
use mod_workflow\workflow;
use mod_workflow\table\requests;


require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/format/lib.php');
require_login();

global $USER, $DB;

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'workflow');
$instance = $DB->get_record('workflow', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$workflow = $DB->get_record('workflow', ['id' => $cm->instance]);

$PAGE->set_url(new moodle_url('/mod/workflow/view.php'));
$PAGE->set_context($context);
$PAGE->set_title($course->shortname . ': ' . $workflow->name);
$PAGE->set_heading($workflow->name);
$PAGE->set_cm($cm, $course);

$cap_approve = has_capability('mod/workflow:approverequest', $context);
$cap_validate = has_capability('mod/workflow:validaterequest', $context);
$cap_create = has_capability('mod/workflow:createrequest', $context);

echo $OUTPUT->header();

$request_manager = new request();
$workflow_manager = new workflow();
$requests = $request_manager->getAllRequests();
$cmid = $cm->id;

if ($cap_approve) {
    $workflowid = $workflow_manager->getWorkflowbyCMID($cmid)->id;
    $activityname = $workflow_manager->getActivityName($workflowid);
    $workflow_cur = $workflow_manager->getWorkflow($workflowid);
    if ($workflow_cur->instructorid == '0') {
        $requests = $request_manager->getAllPendingRequestsbyWorkflow($workflowid);
    } else {
        $requests = $request_manager->getValidRequestsByWorkflow($workflowid);
    }
    $requests = $request_manager->processRequests($requests);
    foreach ($requests as $request) {
        $user = $DB->get_record('user', array('id' => $request->studentid));
        $request->student = $user->firstname . ' ' . $user->lastname;
    }

    $templatecontext = (object)[
        'requests' => array_values($requests),
        'norequests' => array_values($requests) == [],
        'url' => $CFG->wwwroot . '/mod/workflow/approve.php?id=',
        'url_bulk' => $CFG->wwwroot . '/mod/workflow/bulk.php?cmid=',
        'cmid' => $cmid,
        'workflow' => $workflow->name,
        'description' => $workflow->description,
        'activity' => $activityname,
        'workflowtype' => $workflow->type,
        'general' => $workflow->type == 'general',
        'activityrelated' => $workflow->type == 'activity-related',
        'noinstructor' => $workflow->instructorid == '0',
    ];
    echo $OUTPUT->render_from_template('mod_workflow/requests_lecturer', $templatecontext);
} else if ($cap_validate) {
    $workflowid = $workflow_manager->getWorkflowbyCMID($cmid)->id;
    $activityname = $workflow_manager->getActivityName($workflowid);
    $instructor = $workflow_manager->getInstructor($workflowid);
    if ($USER->id === $instructor) {
        $requests = $request_manager->getRequestsByWorkflow($workflowid);
        $requests = $request_manager->processRequests($requests);
        foreach ($requests as $request) {
            if ($request->activityid != '') {
                if ($request->activityid[0] == 'q') {
                    $activity = $DB->get_record('quiz', array('id' => substr($request->activityid, 1)));
                    $activity_name = $activity->name;
                } else if ($request->activityid[0] == 'a') {
                    $activity = $DB->get_record('assign', array('id' => substr($request->activityid, 1)));
                    $activity_name = $activity->name;
                }
                $request->activity = $activity_name;
            } else {
                $request->activity = 'None';
            }
        }
        foreach ($requests as $request) {
            $user = $DB->get_record('user', array('id' => $request->studentid));
            $request->student = $user->firstname . ' ' . $user->lastname;
        }
        $templatecontext = (object)[
            'requests' => array_values($requests),
            'norequests' => array_values($requests) == [],
            'text' => 'text',
            'url' => $CFG->wwwroot . '/mod/workflow/validate.php?id=',
            'cmid' => $cm->id,
            'workflow' => $workflow->name,
            'description' => $workflow->description,
            'activity' => $activityname,
            'workflowtype' => $workflow->type,
            'general' => $workflow->type == 'general',
            'activityrelated' => $workflow->type == 'activity-related',
        ];
        echo $OUTPUT->render_from_template('mod_workflow/requests_instructor', $templatecontext);
    } else {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id, 'You are not assigned to this workflow', null, \core\output\notification::NOTIFY_ERROR);
    }
} else if ($cap_create) {
    $workflowid = $workflow_manager->getWorkflowbyCMID($cmid)->id;
    $activityname = $workflow_manager->getActivityName($workflowid);
    $requests = $request_manager->getRequestsByWorkflow_Student($USER->id, $workflowid);
    $requests = $request_manager->processRequests($requests);
    foreach ($requests as $request) {
        if ($request->activityid != '') {
            if ($request->activityid[0] == 'q') {
                $activity = $DB->get_record('quiz', array('id' => substr($request->activityid, 1)));
                $activity_name = $activity->name;
            } else if ($request->activityid[0] == 'a') {
                $activity = $DB->get_record('assign', array('id' => substr($request->activityid, 1)));
                $activity_name = $activity->name;
            }
            $request->activity = $activity_name;
        } else {
            $request->activity = 'None';
        }
    }
    foreach ($requests as $request) {
        $user = $DB->get_record('user', array('id' => $request->studentid));
        $request->student = $user->firstname . ' ' . $user->lastname;
    }
    $templatecontext = (object)[
        'requests' => array_values($requests),
        'norequests' => array_values($requests) == [],
        'text' => 'text',
        'url' => $CFG->wwwroot . '/mod/workflow/validate.php?id=',
        'cmid' => $cmid,
        'workflow' => $workflow->name,
        'description' => $workflow->description,
        'activity' => $activityname,
        'general' => $workflow->type == 'general',
        'activityrelated' => $workflow->type == 'activity-related',
        'noinstructor' => $workflow->instructorid == '0',
    ];
    $workflow_curr = $workflow_manager->getWorkflow($workflowid);
    $now = time();
    $startdate = (int)$workflow_curr->startdate;
    $enddate = (int)$workflow_curr->enddate;
    if (($startdate == 0 and $enddate == 0) or
        ($startdate <= $now  and $now <= $enddate) or
        ($startdate == 0 and $enddate >= $now) or
        ($startdate <= $now and $enddate == 0)
    ) {
        $createurl = $CFG->wwwroot . '/mod/workflow/create.php?cmid=' . $cm->id . '&workflowid=' . $workflowid;
        echo '<a class="btn btn-primary" href="' . $createurl . '">Create New Request</a><br><br>';
    }

    $period = '';
    if ($startdate != 0 and $enddate != 0) {
        $period = 'from ' . date("Y-m-d, H:i:s", $startdate) . ' until ' . date("Y-m-d, H:i:s", $enddate);
    } else if ($startdate == 0 and $enddate != 0) {
        $period = ' until ' . date("Y-m-d, H:i:s", $enddate);
    } else if ($startdate != 0 and $enddate == 0) {
        $period = 'from ' . date("Y-m-d, H:i:s", $startdate);
    }
    $html = 'Requests are accepted ' . $period . '<br><br>';
    if (!($startdate == 0 and $enddate == 0)) {
        echo $html;
    }
    echo $OUTPUT->render_from_template('mod_workflow/requests_student', $templatecontext);
}

echo $OUTPUT->footer();
