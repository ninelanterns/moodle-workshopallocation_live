<?php

defined('MOODLE_INTERNAL') || die();

$handlers = array(

    'assessable_content_uploaded' => array(
        'handlerfile'       => '/mod/workshop/allocation/live/lib.php',
        'handlerfunction'   => 'workshopallocation_live_assessable_content_uploaded',
        'schedule'          => 'instant',
        'internal'          => 1,
    ),
);
