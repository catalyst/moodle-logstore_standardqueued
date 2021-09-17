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
 * @package    logqueue_sqs
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_standardqueued\local\queue;

defined('MOODLE_INTERNAL') || die();

class sqs implements queue_interface {
    /** @var array $deps list of dependancies */
    public static $deps = ['aws'];

    /** @var string $logguests true if logging guest access */
    protected $logguests;

    public function __construct() {
    }

    /**
     * Push the events to the queue.
     *
     * @param array $evententries raw event data
     */
    public function push_entries(array $evententries) {
        throw new \Exception("A");
    }

    /**
     * Pull the events from the queue.
     *
     * @param int $num max number of events to pull
     */
    public function pull_entries($num=null) {
        throw new \Exception("A");
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
