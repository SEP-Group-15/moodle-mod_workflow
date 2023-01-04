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

class request
{
    public function changeStatus(string $id, string $status)
    {
        global $DB;
        $sql = 'update {request} set status = :status where id= :id';
        $params = [
            'status' => $status,
            'id' => $id,
        ];

        try {
            return $DB->execute($sql, $params);
        } catch (dml_exception $e) {
            return false;
        }
    }

    public function validate(string $id, string $status, string $ins_comment = "")
    {
        global $DB;
        $this->changeStatus($id, $status);
        $sql = 'update {request} set instructorcomment = :ins_comment where id= :id';
        $params = [
            'ins_comment' => $ins_comment,
            'id' => $id,
        ];
        try {
            return $DB->execute($sql, $params);
        } catch (dml_exception $e) {
            return false;
        }
    }

    public function approve(string $id, string $status, string $lec_comment = "")
    {
        global $DB;
        $this->changeStatus($id, $status);
        $sql = 'update {request} set lecturercomment = :lec_comment where id= :id';
        $params = [
            'lec_comment' => $lec_comment,
            'id' => $id,
        ];
        try {
            return $DB->execute($sql, $params);
        } catch (dml_exception $e) {
            return false;
        }
    }

    public function processExtensions($activityid, $studentid, $extended_date, $type, $isbatchreq)
    {
        global $DB;
        $record = new stdClass();
        $pure_id = substr($activityid, 1);
        if ($isbatchreq === '0') {
            if (strpos($activityid, 'a') === 0) {
                $table = 'assign_overrides';
                $record->assignid = $pure_id;
                $record->duedate = $extended_date;

            } else if (strpos($activityid, 'q') === 0) {
                $table = 'quiz_overrides';
                $record->quiz = $pure_id;
                $timegap = $this->getTimeGap($pure_id, 'timeopen', 'timeclose', 'quiz');
                $record->timeopen = $extended_date;
                $record->timeclose = $extended_date + $timegap;
            }
            
            $record->userid = $studentid;
            try {
                return $DB->insert_record($table, $record, false);
            } catch (dml_exception $e) {
                return false;
            }
        } else if ($isbatchreq === '1') {
            if (strpos($activityid, 'a') === 0) {
                $table = 'assign';
                $record->id = $pure_id;
                $record->duedate = $extended_date;

            } else if (strpos($activityid, 'q') === 0) {
                $table = 'quiz';
                $timegap = $this->getTimeGap($pure_id, 'timeopen', 'timeclose', 'quiz');
                $record->id = $pure_id;
                $record->timeopen = $extended_date;
                $record->timeclose = $extended_date + $timegap;

            }

            try{
                return $DB->update_record($table,$record);
            }catch (dml_exception $e){
                return false;
            }
        }
    }

    public function createRequest(
        $request,
        $workflowid,
        $studentid,
        $activityid,
        $type,
        $isbatchrequest,
        $timecreated,
        $files,
        $filename,
        $instructorcomment = "",
        $lecturercomment = ""
    )
    {

        global $DB;
        $record = new stdClass();
        $record->request = $request;
        $record->workflowid = $workflowid;
        $record->studentid = $studentid;
        $record->activityid = $activityid;
        $record->type = $type;
        $record->status = 'pending';
        $record->isbatchrequest = $isbatchrequest;
        $record->timecreated = $timecreated;
        $record->files = $files;
        $record->instructorcomment = $instructorcomment;
        $record->lecturercomment = $lecturercomment;
        $record->filename = $filename;

        try {
            return $DB->insert_record('request', $record, false);
        } catch (dml_exception $e) {
            return false;
        }
    }

    public function getAllRequests()
    {
        global $DB;
        try {
            return $DB->get_records('request');
        } catch (dml_exception $e) {
            return [];
        }
    }

    public function filterRequests(string $type)
    {
        global $DB;
        return $DB->get_records_select('request', 'type = :type', [
            'type' => $type
        ]);
    }

    public function getStatus($requestid)
    {
        global $DB;
        $sql = 'id = :id;';
        $params = [
            'id' => $requestid,
        ];

        return $DB->get_field_select('request', 'status', $sql, $params);
    }

    public function getRequest($requestid)
    {
        global $DB;
        return $DB->get_record(
            'request',
            [
                'id' => $requestid
            ]
        );
    }

    public function getAllPendingRequestsbyWorkflow($workflowid){
        global $DB;
        return $DB->get_records_select('request', 'workflowid = :workflowid and status=:status', [
            'workflowid' => $workflowid,
            'status' => 'pending'
        ]);
    }
    public function getRequestsByWorkflow($cmid)
    {
        global $DB;
        return $DB->get_records_select('request', 'workflowid = :workflowid', [
            'workflowid' => $cmid
        ]);
    }

    public function getValidRequestsByWorkflow($workflowid)
    {
        global $DB;
        return $DB->get_records_select('request', 'workflowid = :workflowid and status=:status', [
            'workflowid' => $workflowid,
            'status' => 'valid',
        ]);
    }

    public function getRequestsByWorkflow_Student($userid, $cmid)
    {
        global $DB;
        return $DB->get_records_select('request', 'workflowid = :workflowid and studentid=:userid', [
            'workflowid' => $cmid,
            'userid' => $userid
        ]);
    }

    public function processRequests($requests)
    {
        foreach ($requests as $request) {
            $request->status = ucwords($request->status);
            $request->timecreated = date("Y-m-d H:i:s", $request->timecreated);
        }
        return $requests;
    }

    public function getActivityId($requestid)
    {
        global $DB;
        $sql = 'id=:id';
        $params = [
            'id' => $requestid
        ];
        return $DB->get_field_select('request', 'activityid', $sql, $params);
    }

    public function getActivityName($requestid)
    {
        global $DB;

        $activityid = $this->getActivityId($requestid);
        if ($activityid === ''){
            return 'General';
        }
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

    public function getStudentID($id)
    {
        global $DB;
        $sql = 'id=:id';
        $params = [
            'id' => $id
        ];
        return $DB->get_field_select('request', 'studentid', $sql, $params);
    }

    public function finalizeRequest(
        $id,
        $validity,
        $timestamp,
        $lec_comment
    )
    {
        $request = $this->getRequest($id);

        $status = array();
        $status['0'] = "approved";
        $status['1'] = "rejected";

        $this->approve(
            $id,
            $status[$validity],
            $lec_comment
        );
        if ($status[$validity] === "approved" && $request->activityid != '') {

            $this->processExtensions(
                $request->activityid,
                $request->studentid,
                $timestamp,
                $request->type,
                $request->isbatchrequest
            );
        }

    }

    public function getTimeGap($id, $start, $end, $table) {
        global $DB;
        $record = $DB->get_record(
            $table,
            [
                'id' => $id
            ]
        );
        return $record->$end - $record->$start;
    }

}
