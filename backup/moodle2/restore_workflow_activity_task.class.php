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
 * Define all the restore steps that will be used by the restore_workflow_activity_task
 *
 * @package    mod_workflow
 * @copyright  2022 SEP15
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/workflow/backup/moodle2/restore_workflow_stepslib.php');

class restore_workflow_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new restore_workflow_activity_structure_step('workflow_structure', 'workflow.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('workflow', array('description'), 'workflow');
        $contents[] = new restore_decode_content('workflow_request',
                array('request','instructorcomment','lecturercomment'), 'workflow_request');
        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('WORKFLOWVIEWBYID',
                    '/mod/workflow/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('WORKFLOWAPPROVEBYIDCMID',
                    '/mod/workflow/approve.php?id=$1&cmid=$2', array('workflow_request', 'course_module'));
        $rules[] = new restore_decode_rule('WORKFLOWCREATEBYIDCMID',
        '/mod/workflow/approve.php?cmid=$1&workflowid=$2', array('course_module', 'workflow'));
        $rules[] = new restore_decode_rule('WORKFLOWVALIDATEBYIDCMID',
                    '/mod/workflow/validate.php?id=$1&cmid=$2', array('workflow_request', 'course_module'));
        $rules[] = new restore_decode_rule('WORKFLOWINDEX',
        '/mod/workflow/index.php?id=$1', 'course');
        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * workflow logs. It must return one array
     * of {@see restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = array();

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@see restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = array();

        return $rules;
    }
}
