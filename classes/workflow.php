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

namespace mod_workflow;

use stdClass;
use dml_exception;

class workflow
{
    public function create($name, $description, $courseid, $activityid, $instructorid, $startdate, $enddate, $commentsallowed, $filesallowed, $representativeid, $lecturerid)
    {
        global $DB;
        $record = new stdClass();
        $record->name = $name;
        $record->description = $description;
        $record->courseid = $courseid;
        $record->activityid = $activityid;
        $record->instructorid = $instructorid;
        $record->startdate = $startdate;
        $record->enddate = $enddate;
        $record->commentsallowed = $commentsallowed;
        $record->filesallowed = $filesallowed;
        $record->representativeid = $representativeid;
        $record->lecturerid = $lecturerid;

        try {
            return $DB->insert_record('workflow', $record, false);
        } catch (dml_exception $e) {
            return false;
        }
    }

    public function remove(string $id)
    {
        global $DB;
        $DB->delete_records_select('workflow', 'id = ?', [$id]);
    }

    public function getActivityName($workflowid)
    {
        global $DB;
        $workflow = $this->getWorkflow($workflowid);

        if ($workflow->type == 'general') {
            return 'None';
        } else {
            $activityid = $workflow->activityid;
            $pure_activityid = substr($activityid, 1);

            $sql = 'id=:id';
            $params = [
                'id' => $pure_activityid
            ];
            if (substr($activityid, 0, 1) === 'a') {
                $table = 'assign';
                $name = 'name';
            } else if (substr($activityid, 0, 1) === 'q') {
                $table = 'quiz';
                $name = 'name';
            }

            return $DB->get_field_select($table, $name, $sql, $params);
        }
    }

    public function getName(string $id)
    {
        global $DB;
        $sql = 'id = :id;';
        $params = [
            'id' => $id,
        ];

        return $DB->get_field_select('workflow', 'name', $sql, $params);
    }

    public function getWorkflow(string $id)
    {
        global $DB;
        return $DB->get_record(
            'workflow',
            [
                'id' => $id
            ]
        );
    }

    public function getAllWorkflows()
    {
        global $DB;
        try {
            return $DB->get_records('workflow');
        } catch (dml_exception $e) {
            return [];
        }
    }

    public function getWorkflowbyCMID($cmid)
    {
        global $DB;
        $sql = 'id=:cmid';
        $params = [
            'cmid' => $cmid
        ];
        $workflowid = $DB->get_field_select('course_modules', 'instance', $sql, $params);
        $workflow = $this->getWorkflow($workflowid);
        return $workflow;
    }

    public function getRepresentativeId($workflowid)
    {
        global $DB;

        $sql = 'id=:id';
        $params = [
            'id' => $workflowid
        ];
        return $DB->get_field_select('workflow', 'representativeid', $sql, $params);
    }

    public function getInstructor($workflowid)
    {
        global $DB;
        $sql = 'id=:id';
        $params = [
            'id' => $workflowid
        ];
        return $DB->get_field_select('workflow', 'instructorid', $sql, $params);
    }

    public function getActivityid($workflowid)
    {
        global $DB;
        $sql = 'id=:id';
        $params = [
            'id' => $workflowid
        ];
        $activityid = $DB->get_field_select('workflow', 'activityid', $sql, $params);
        $type = $activityid[0];
        $id = substr($activityid, 1);
        $params = [
            'id' => $id
        ];
        if ($type == 'a') {
            $activity_name = $DB->get_field_select('assign', 'name', $sql, $params);
        } else {
            $activity_name = $DB->get_field_select('quiz', 'name', $sql, $params);
        }
        return array($activityid, $activity_name);
    }
}
