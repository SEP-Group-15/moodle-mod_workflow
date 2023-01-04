<?php 

defined('MOODLE_INTERNAL') || die();

$messageproviders = array (
    // Notify teacher that a student has submitted a quiz attempt
    'workflow_notification' => array (
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED 
        ],
    ),
);