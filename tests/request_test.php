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
 * Unit tests for mod_worklow
 *
 * @package    mod_worklow
 * @category   phpunit
 */

defined('MOODLE_INTERNAL') || die();

use mod_workflow\request;

global $CFG;
require_once($CFG->dirroot . '/mod/workflow/lib.php');

class mod_workflow_request_test extends advanced_testcase
{
    public function test_createRequest()
    {
        $this->resetAfterTest();
        $this->setUser(2);
        $request = new request();
        $requests = $request->getAllRequests();
        $this->assertEmpty($requests);

        $requestText = "Test Request";
        $worflowid = "100";
        $studentid = "100";
        $activityid = "q100";
        $type = "deadline extension";
        $isbatchrequest = 0;
        $timecreated = 1;
        $files = 3;
        $filename = "filename.pdf";
        $instructorcomment = "Test comments";
        $lecturercomment = "Test feedback";

        $result = $request->createRequest(
            $requestText,
            $worflowid,
            $studentid,
            $activityid,
            $type,
            $isbatchrequest,
            $timecreated,
            $files,
            $filename,
            $instructorcomment,
            $lecturercomment
        );

        $this->assertTrue($result);
        $requests = $request->getAllRequests();
        $this->assertNotEmpty($requests);

        $record = array_pop($requests);

        $this->assertEquals("Test Request", $record->request);
        $test_request_status = $request->getStatus($record->id);
        $this->assertEquals("pending", $test_request_status);
    }

    public function test_changeStatus()
    {
        $this->resetAfterTest();
        $this->setUser(2);
        $request = new request();
        $requests = $request->getAllRequests();
        $this->assertEmpty($requests);

        $requestText = "Test Request";
        $worflowid = "100";
        $studentid = "100";
        $activityid = "q100";
        $type = "deadline extension";
        $isbatchrequest = 0;
        $timecreated = 1;
        $files = 3;
        $filename = "filename.pdf";
        $instructorcomment = "Test comments";
        $lecturercomment = "Test feedback";

        $request->createRequest(
            $requestText,
            $worflowid,
            $studentid,
            $activityid,
            $type,
            $isbatchrequest,
            $timecreated,
            $files,
            $filename,
            $instructorcomment,
            $lecturercomment
        );

        $requests = $request->getAllRequests();
        $record = array_pop($requests);

        $request->changeStatus($record->id, 'valid');
        $test_request_status = $request->getRequest($record->id)->status;
        $this->assertEquals("valid", $test_request_status);
    }

    public function test_filterRequests()
    {
        $this->resetAfterTest();
        $this->setUser(2);
        $request = new request();
        $requests = $request->getAllRequests();
        $this->assertEmpty($requests);

        $requestText = "Test Request";
        $worflowid = "100";
        $studentid = "100";
        $activityid = "q100";
        $isbatchrequest = 0;
        $timecreated = 1;
        $files = 3;
        $filename = "filename.pdf";
        $instructorcomment = "Test comments";
        $lecturercomment = "Test feedback";

        for ($i = 0; $i < 10; $i++) {
            $temp = $requestText . $i;

            if ($i < 4) {
                $temp_type = 'Deadline extension';
            } else {
                $temp_type = 'Late submission';
            }
            $request->createRequest(
                $temp,
                $worflowid,
                $studentid,
                $activityid,
                $temp_type,
                $isbatchrequest,
                $timecreated,
                $files,
                $filename,
                $instructorcomment,
                $lecturercomment
            );
        }

        $this->assertEquals(4, count($request->filterRequests('Deadline extension')));
        $this->assertEquals(6, count($request->filterRequests('Late submission')));
    }
}
