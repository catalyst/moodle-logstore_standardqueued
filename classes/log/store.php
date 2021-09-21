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

/**
 * Standard log reader/writer.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class store extends base_store {
    /** @var string $replacing We pretend that we are logstore_standard */
    public static $replacing = 'logstore_standard';

    /** @var array $queueclasses in the order of preference XXX config? */
    public static $queueclasses = ['sqs'];

    /** @var array $configerrors queue config errors */
    public static $configerrors = [];

    /** @var queue_interface $queue configired queue */
    protected $queue;

    /**
     * Push the events to the queue.
     */
    public static function configured_queue() {
        self::$configerrors = [];
        foreach (self::$queueclasses as $cls) {
            $class = "\\logstore_standardqueued\\queue\\$cls";
            $q = new $class();
            if ($q->is_configured()) {
                return $q;
            } else {
                if ($q->configerror) {
                    self::$configerrors[] = $q->configerror;
                }
            }
        }
    }

    /**
     * Check double logstore_standard enablement.
     *
     * @return bool true means both logstore_standard and logstore_standardqueued are enabled
     */
    public static function both_logstore_standard_enabled() {
        $plugins = get_config('tool_log', 'enabled_stores');
        return in_array(self::$replacing, explode(',', $plugins));
    }

    /**
     * Store constructor.
     *
     * @param \tool_log\log\manager $manager Log manages.
     */
    public function __construct(manager $manager) {
        parent::__construct($manager);

        $this->component = self::$replacing;
        $this->buffersize = $this->get_config('buffersize', 50);
        $this->queue = self::configured_queue();
    }

    /**
     * Are the new events appearing in the reader?
     *
     * @return bool true means new log events are being added, false means no new data will be added
     */
    public function is_logging() {
        // Only enabled stpres are queried,
        // this means we can return true here unless store has some extra switch.
        return !self::both_logstore_standard_enabled();
    }

    /**
     * Push the events to the queue.
     *
     * @param array $evententries raw event data
     */
    protected function insert_event_entries($evententries) {
        $errorentries = [];
        if ($this->queue) {
            foreach ($evententries as $entry) {
                try {
                    $this->queue->push_entry($entry);
                } catch (Exception $e) {
                    debugging(
                        "logstore_standardqueued: Failed to push event to the queue: ".
                        $e->getMessage()
                    );
                    $errorentries[] = $entry;
                }
            }
        } else {
            $errorentries = $evententries;
        }

        if ($errorentries) {
            // Fallback to standard non-queued behaviour.
            $this->insert_queued_event_entries($errorentries);
        }
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
