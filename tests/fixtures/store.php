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
 * Standard log queue
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_standardqueued_test\log;

use Exception;
use tool_log\log\manager, logstore_standardqueued\queue\queue_interface;

use logstore_standardqueued\log\store as tested_store;

defined('MOODLE_INTERNAL') || die();

class store extends tested_store {
    /** @var bool */
    public static $bad = false;

    public function __construct(\tool_log\log\manager $manager) {
        parent::__construct($manager);

        $this->queue = self::$bad ? (new test_queue_bad()) : (new test_queue_good());
    }
}

class test_queue_good implements queue_interface {
    /** @var array $events */
    private static $events = [];

    /**
     * Push the events to the queue.
     *
     * @param array $evententries raw event data
     */
    public function push_entries(array $evententries) {
        self::$events = array_merge(self::$events, $evententries);
    }

    /**
     * Pull the events from the queue.
     *
     * @param int $num max number of events to pull
     */
    public function pull_entries($num=null) {
        if (!$num) {
            $num = count(self::$events);
        }
        return array_splice(self::$events, 0, $num);
    }

    /**
     * Can we use this queue?
     *
     * @return bool
     */
    public function is_configured() {
        return true;
    }
}

class test_queue_bad implements queue_interface {
    /**
     * Push the events to the queue.
     *
     * @param array $evententries raw event data
     */
    public function push_entries(array $evententries) {
        throw new Exception("I'm bad");
    }

    /**
     * Pull the events from the queue.
     *
     * @param int $num max number of events to pull
     */
    public function pull_entries($num=null) {
        return [];
    }

    /**
     * Can we use this queue?
     *
     * @return bool
     */
    public function is_configured() {
        return true;
    }
}
