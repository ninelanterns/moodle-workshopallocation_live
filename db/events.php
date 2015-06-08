<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname'   => '\mod_workshop\event\assessable_uploaded',
        'includefile' => '/mod/workshop/allocation/live/lib.php',
        'callback'    => 'workshopallocation_live_assessable_uploaded',
        'internal'    => true,
    ),
);
