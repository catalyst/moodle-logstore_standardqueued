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
 * Standard log reader/writer.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_standardqueued\log;

defined('MOODLE_INTERNAL') || die();

use core_component, core_plugin_manager;
use logstore_standard\log\store as base_store;

class store extends base_store {
    /** @var array $queues configired queues */
    protected $queues = [];

    public function __construct(\tool_log\log\manager $manager) {
        parent::__construct($manager);

        foreach (core_plugin_manager::instance()->get_plugins_of_type('logqueue') as $plugin) {
        }
        foreach(core_component::get_plugin_list_with_class('logqueue', 'queue') as $transport) {
        }
    }

    /**
     * Push the events to the queue.
     *
     * @param array $evententries raw event data
     */
    protected function insert_event_entries($evententries) {
        throw new Exception("A");
        $this->insert_queued_event_entries($evententries);
    }

    /**
     * Finally store the events into the database.
     *
     * @param array $evententries raw event data
     */
    protected function insert_queued_event_entries($evententries) {
        parent::insert_event_entries($evententries);
    }
}
