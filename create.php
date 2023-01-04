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

use mod_workflow\form\create;
use mod_workflow\request;
use mod_workflow\workflow;

require_once(__DIR__ . '/../../config.php'); // setup moodle
require_login();

global $DB, $SESSION;

$workflowid = optional_param('workflowid', null, PARAM_INT);
$cmid = optional_param('cmid', true, PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'workflow');
$context = context_module::instance($cm->id);

$PAGE->set_url(new moodle_url('/mod/workflow/create.php'));
$PAGE->set_context($context);
$PAGE->set_title('Create Request');
$PAGE->set_heading('Create Request');
$PAGE->navbar->add('Create Request');
$PAGE->set_cm($cm, $course);

$SESSION->workflowid = $workflowid;

$mform = new create();

if ($mform->is_cancelled()) {
    //go back to manage page
    redirect($CFG->wwwroot . '/mod/workflow/view.php?id=' . $cmid, 'Request is Cancelled');
} else if ($fromform = $mform->get_data()) {
    $SESSION->workflowid = $fromform->workflowid;
    $workflow = new workflow();
    $types['0'] = "Deadline extension";
    $types['1'] = "Failure to attempt";
    $types['2'] = "Late submission";
    $types['3'] = "Other";
    if (isset($fromform->type)) {
        $type = $types[$fromform->type];
    } else {
        $type = '';
    }
    $request_manager = new request();
    $wm = $workflow->getWorkflowbyCMID($cmid)->id;
    $workflowid = $workflow->getWorkflowbyCMID($cmid)->id;
    $t = time();
    $name = $mform->get_new_filename('files');
    if ($name != '0') {
        $filename = $name;
    } else {
        $filename = '';
    }
    $uploadFolder = __DIR__ . '\\uploads\\';
    mkdir($uploadFolder . $fromform->files, 0777, true);
    $success = $mform->save_file('files', $uploadFolder . $fromform->files . '\\' . $name);
    $request_manager->createRequest(
        $fromform->request,
        $workflowid,
        $USER->id,
        $fromform->activityid,
        $type,
        $fromform->isbatchrequest,
        $t,
        $fromform->files,
        $filename,
        "",
        ""
    );
    redirect($CFG->wwwroot . '/mod/workflow/view.php?id=' . $fromform->cmid, 'Request is submitted');
}

echo $OUTPUT->header();
$temp = new stdClass();
$temp->cmid = $cmid;
$mform->set_data($temp);
$mform->display();
echo $OUTPUT->footer();
