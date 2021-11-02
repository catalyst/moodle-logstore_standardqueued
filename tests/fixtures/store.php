<?php
/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

 * Standard log queue
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_standardqueued_test\log;

use Exception;
use tool_log\log\manager;
use logstore_standardqueued\queue\queue_interface;

use logstore_standardqueued\log\store as tested_store;

defined('MOODLE_INTERNAL') || die();

/**
 * Test store.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class store extends tested_store
{
    // @var bool
    public static $bad = false;

    /**
     * Constructor.
     *
     * @param \tool_log\log\manager $manager Log manages.
     */
    public function __construct(manager $manager)
    {
        parent::__construct($manager);

        $this->queue = self::$bad ? (new test_queue_bad()) : (new test_queue_good());
    }

    /**
     * Bad queue exception message.
     *
     * @return string $message exception message
     */
    public function exception_message()
    {
        return test_queue_bad::$message;
    }
}

/**
 * Test queue.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_queue_good implements queue_interface
{
    // @var array $events 
    private static $events = [];

    /**
     * A line describing the queue and its config.
     *
     * @return string $info informational string about the queue.
     */
    public function details()
    {
        return "test_queue good";
    }

    /**
     * Push the event to the queue.
     *
     * @param array $evententry raw event data
     */
    public function push_entry(array $evententry)
    {
        self::$events[] = $evententry;
    }

    /**
     * Pull the events from the queue.
     *
     * @param int $num max number of events to pull
     */
    public function pull_entries($num=null)
    {
        if (!$num) {
            $num = count(self::$events);
        }
        return array_splice(self::$events, 0, $num);
    }

    /**
     * Did we configure this queue?
     *
     * @return bool
     */
    public function is_configured()
    {
        return true;
    }

    /**
     * Can we use this queue?
     *
     * @return bool
     */
    public function is_operational()
    {
        return true;
    }
}

/**
 * Test queue.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_queue_bad implements queue_interface
{
    // @var string $events
    public static $message = "I'm bad";

    /**
     * A line describing the queue and its config.
     *
     * @return string $info informational string about the queue
     */
    public function details()
    {
        return "test_queue bad";
    }

    /**
     * Push the event to the queue.
     *
     * @param array $evententry raw event data
     */
    public function push_entry(array $evententry)
    {
        throw new Exception(self::$message);
    }

    /**
     * Pull the events from the queue.
     *
     * @param int $num max number of events to pull
     */
    public function pull_entries($num=null)
    {
        return [];
    }

    /**
     * Did we configure this queue?
     *
     * @return bool
     */
    public function is_configured()
    {
        return true;
    }

    /**
     * Can we use this queue?
     *
     * @return bool
     */
    public function is_operational()
    {
        return true;
    }
}
