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
 * Structure step to restore one workflow activity
 *
 * @package    mod_workflow
 * @copyright  2022 SEP15
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class restore_workflow_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the restore workflow.
     *
     * @return restore_path_element $structure
     */
    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo'); // Are we including userinfo?

        // XML interesting paths - non-user data.
        $paths[] = new restore_path_element('workflow', '/activity/workflow');

        $paths[] = new restore_path_element('workflow_request',
                       '/activity/workflow/requests/request');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process an workflow restore.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_workflow($data) {
        global $DB;

        $data = (object)$data;
        $data->courseid = $this->get_courseid();

        // Insert the workflow record.
        $newitemid = $DB->insert_record('workflow', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process workflow status restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_workflow_request($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        
        $newitemid = $DB->insert_record('workflow_request', $data);
        $this->set_mapping('workflow_request', $oldid, $newitemid);
    }

    /**
     *  Runs after restore execution
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_workflow', 'request', 'workflow_request');
    }
}
