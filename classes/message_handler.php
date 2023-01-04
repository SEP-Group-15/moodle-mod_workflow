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

class message_handler{
    
    public function send($usertoid,$msg,$cmid){
        global $USER, $DB;

        $message = new \core\message\message();
        $message->component = 'mod_workflow'; // Your plugin's name
        $message->name = 'workflow_notification'; // Your notification name from message.php
        $message->userfrom = $USER; // If the message is 'from' a specific user you can set them here
        $message->userto =  $DB->get_record('user', array('id' => $usertoid), '*', MUST_EXIST);
        $message->subject = 'Student Request';
        $message->fullmessage = $msg;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = '<p>'.$msg.'</p>';
        $message->smallmessage = $msg;
        $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message
        $message->contexturl = (new \moodle_url('/mod/workflow/view.php?id='.$cmid))->out(false); // A relevant URL for the notification
        $message->contexturlname = 'Student Requests'; // Link title explaining where users get to for the contexturl
        $content = array('*' => array('header' => ' test ', 'footer' => ' test ')); // Extra content for specific processor
        $message->set_additional_content('email', $content);

        $messageid = message_send($message);
    }

}