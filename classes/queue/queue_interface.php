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
 * Log store interface.
 *
 * @package    logstore_standardqueued
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_standardqueued\queue;

defined('MOODLE_INTERNAL') || die();

interface queue_interface {
    /**
     * A line describing the queue and its config.
     *
     * @return string $info informational string about the queue.
     */
    public function details();

    /**
     * Push the event to the queue.
     *
     * @param array $evententry raw event data
     */
    public function push_entry(array $evententry);

    /**
     * Pull the events from the queue.
     *
     * @param int $num max number of events to pull
     * @return array $evententries raw event data
     */
    public function pull_entries($num=null);

    /**
     * Can we use this queue?
     *
     * @return bool
     */
    public function is_configured();
}
