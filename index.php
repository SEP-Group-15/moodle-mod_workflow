<?php

require_once("../../config.php");

$id = required_param('id', PARAM_INT);

redirect(new moodle_url('/mod/workflow/index.php', array('id' => $id)));