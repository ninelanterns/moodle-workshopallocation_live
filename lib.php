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
 * Allocates late submissions using the scheduled allocator settings
 *
 * @package    workshopallocation_live
 * @subpackage mod_workshop
 * @copyright  2014 Albert Gasset <albertgasset@fsfe.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/mod/workshop/locallib.php');
require_once($CFG->dirroot.'/mod/workshop/allocation/lib.php');
require_once($CFG->dirroot.'/mod/workshop/allocation/random/lib.php');

class workshop_live_allocator_form extends moodleform {

    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $workshop = $this->_customdata['workshop'];

        $plugindefaults = get_config('workshopallocation_random');

        $strenbaled = get_string('enabled', 'workshopallocation_live');
        $cm = get_coursemodule_from_instance('workshop', $workshop->id, 0, false, MUST_EXIST);
        $url = new moodle_url($PAGE->url, array('method' => 'scheduled', 'cmid' => $cm->id));
        $strenabledinfo = get_string('enabledinfo', 'workshopallocation_live', $url->out());

        $mform->addElement('checkbox', 'enabled', $strenbaled, $strenabledinfo);

        $this->add_action_buttons();
    }
}

class workshop_live_allocator implements workshop_allocator {

    protected $workshop;
    protected $mform;

    public function __construct(workshop $workshop) {
        $this->workshop = $workshop;
    }

    public function init() {
        global $PAGE, $DB;

        $result = new workshop_allocation_result($this);

        $customdata = array();
        $customdata['workshop'] = $this->workshop;

        $settings = $DB->get_record('workshopallocation_live',
                                    array('workshopid' => $this->workshop->id));
        if (!$settings) {
            $settings = new stdClass;
            $settings->workshopid = $this->workshop->id;
            $settings->enabled = false;
        }

        $this->mform = new workshop_live_allocator_form($PAGE->url, $customdata);

        if ($this->mform->is_cancelled()) {
            redirect($this->workshop->view_url());
        } else if ($data = $this->mform->get_data()) {
            $settings->enabled = !empty($data->enabled);
            if (isset($settings->id)) {
                $DB->update_record('workshopallocation_live', $settings);
            } else {
                $DB->insert_record('workshopallocation_live', $settings);
            }
            if ($settings->enabled) {
                $msg = get_string('resultenabled', 'workshopallocation_live');
            } else {
                $msg = get_string('resultdisabled', 'workshopallocation_live');
            }
            $result->set_status(workshop_allocation_result::STATUS_CONFIGURED, $msg);
        } else {
            $this->mform->set_data($settings);
            $result->set_status(workshop_allocation_result::STATUS_VOID);
        }

        return $result;
    }

    public function ui() {
        global $PAGE;

        $output = $PAGE->get_renderer('mod_workshop');

        $html = $output->container_start('live-allocator');
        ob_start();
        $this->mform->display();
        $html .= ob_get_contents();
        ob_end_clean();
        $html .= $output->container_end();

        return $html;
    }

    public static function delete_instance($workshopid) {
        global $DB;

        $DB->delete_records('workshopallocation_live', array('workshopid' => $workshopid));
    }
}

function workshopallocation_live_assessable_content_uploaded($event) {
    global $DB;

    $cm = get_coursemodule_from_id('workshop', $event->cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $instance = $DB->get_record('workshop', array('id' => $cm->instance), '*', MUST_EXIST);
    $workshop = new workshop($instance, $cm, $course);

    $enabled = $DB->get_field('workshopallocation_live', 'enabled',
                              array('workshopid' => $workshop->id));

    $scheduled = $DB->get_record('workshopallocation_scheduled',
                                 array('workshopid' => $workshop->id));

    if ($workshop->phase == workshop::PHASE_ASSESSMENT and $enabled and $scheduled) {
        $randomallocator = $workshop->allocator_instance('random');
        $settings = workshop_random_allocator_setting::instance_from_text($scheduled->settings);
        $result = new workshop_allocation_result($randomallocator);
        $randomallocator->execute($settings, $result);
    }

    return true;
}
