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
 * Class {@see backup_workflow_activity_task} definition
 *
 * @package    mod_workflow
 * @copyright  2022 SEP15
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/workflow/backup/moodle2/backup_workflow_stepslib.php');

class backup_workflow_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_workflow_activity_structure_step('workflow_structure', 'workflow.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        $search = "/(" . $base . "\/mod\/workflow\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@WORKFLOWVIEWBYID*$2@$', $content);

        $search = "/(" . $base . "\/mod\/workflow\/approve.php\?id\=)([0-9]+)\&cmid\=([0-9]+)/";
        $content = preg_replace($search, '$@WORKFLOWAPPROVEBYIDCMID*$2*$3@$', $content);

        $search = "/(" . $base . "\/mod\/workflow\/create.php\?cmid\=)([0-9]+)\&workflowid\=([0-9]+)/";
        $content = preg_replace($search, '$@WORKFLOWCREATEBYIDCMID*$2*$3@$', $content);

        $search = "/(" . $base . "\/mod\/workflow\/validate.php\?id\=)([0-9]+)\&cmid\=([0-9]+)/";
        $content = preg_replace($search, '$@WORKFLOWVALIDATEBYIDCMID*$2*$3@$', $content);

        $search="/(".$base."\/mod\/workflow\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@WORKFLOWINDEX*$2@$', $content);

        return $content;
    }
}
