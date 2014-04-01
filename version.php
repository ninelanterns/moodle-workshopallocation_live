<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version    = 2014031900;
$plugin->requires   = 2013050100;
$plugin->component  = 'workshopallocation_live';
$plugin->dependencies = array(
    'workshopallocation_scheduled' => 2013050100,
);
