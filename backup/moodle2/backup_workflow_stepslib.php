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
 * Defines all the backup steps that will be used by {@see backup_workflow_activity_task}
 *
 * @package    mod_workflow
 * @copyright  2022 SEP15
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class backup_workflow_activity_structure_step extends backup_activity_structure_step
{

    /**
     * Define the structure of the backup workflow.
     *
     * @return restore_path_element $structure
     */
    protected function define_structure()
    {

        // XML nodes declaration - non-user data.
        $workflow = new backup_nested_element('workflow', array('id'), array(
            'name', 'description', 'courseid', 'type', 'activityid', 'instructorid',
            'representativeid', 'startdate', 'enddate', 'commentsallowed', 'filesallowed', 'lecturerid'
        ));

        $requests = new backup_nested_element('requests');

        $request = new backup_nested_element('request', array('id'), array(
            'request', 'workflowid', 'studentid', 'activityid', 'type', 'status', 'isbatchrequest', 'timecreated',
            'files', 'filename', 'instructorcomments', 'lecturercomment'
        ));

        // Build the tree in the order needed for restore.
        $workflow->add_child($requests);
        $requests->add_child($request);
        // Data sources - non-user data.

        $workflow->set_source_table('workflow', array('id' => backup::VAR_ACTIVITYID));

        $requests->set_source_table('workflow_request', array('workflowid' => backup::VAR_PARENTID));

        // File annotations.
        $request->annotate_files('mod_workflow', 'request', 'files');

        // Return the root element (workshop), wrapped into standard activity structure.
        return $this->prepare_activity_structure($workflow);
    }
}
