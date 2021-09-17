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

use Exception;
use moodle_exception;

use tool_log\log\manager;
use logstore_standardqueued\queue\queue_interface;

use logstore_standard\log\store as base_store;

class store extends base_store {
    /** @var array $queueclasses in the order of preference XXX config? */
    public static $queueclasses = ['sqs'];

    /** @var queue_interface $queue configired queue */
    protected $queue;

    /**
     * Push the events to the queue.
     *
     * @param array $evententries raw event data
     */
    public static function configured_queue() {
        foreach (self::$queueclasses as $cls) {
            $class = "\\logstore_standardqueued\\queue\\$cls";
            $q = new $class();
            if ($q->is_configured()) {
                return $q;
            }
        }
    }

    public function __construct(manager $manager) {
        // We pretend that we are logstore_standard.
        $replacing = 'logstore_standard';

        $plugins = get_config('tool_log', 'enabled_stores');
        if (in_array($replacing, explode(',', $plugins))) {
            throw new moodle_exception("Cannot have both logstore_standardqueued and $replacing plugins enabled");
        }

        parent::__construct($manager);

        $this->component = $replacing;
        $this->buffersize = $this->get_config('buffersize', 50);
        $this->queue = self::configured_queue();
    }

    /**
     * Push the events to the queue.
     *
     * @param array $evententries raw event data
     */
    protected function insert_event_entries($evententries) {
        if ($this->queue) {
            try {
                return $this->queue->push_entries($evententries);
            } catch (Exception $e) {
                // log
            }
        }

        // Fallback to standard non-queued behaviour.
        $this->insert_queued_event_entries($evententries);
    }

    /**
     * Pull the events from the queue and store them.
     */
    public function store_queued_event_entries() {
        if ($this->queue) {
            $this->insert_queued_event_entries($this->queue->pull_entries());
        }
    }

    /**
     * Store the events into the database.
     *
     * @param array $evententries raw event data
     */
    protected function insert_queued_event_entries($evententries) {
        parent::insert_event_entries($evententries);
    }
}
