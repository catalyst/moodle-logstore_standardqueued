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
 * Adhoc task that performs asynchronous log entries pull from queue.
 *
 * @package    logstore_standardqueued
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_standardqueued\task;

defined('MOODLE_INTERNAL') || die();

use logstore_standardqueued\log\store;

use core\task\adhoc_task;


/**
 * Adhoc task that performs asynchronous log entries pull from queue.
 *
 * @package    logstore_standardqueued
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class asynchronous_pull_task extends adhoc_task {

    /**
     * Constructor
     */
    public function __construct() {
        $this->set_blocking(false);
        $this->set_component('logstore_standardqueued');
    }

    /**
     * Run the adhoc task and preform the backup.
     */
    public function execute() {
        $store = new store(get_log_manager());
        $store->store_queued_event_entries();

        mtrace("Pulled log records from the queue.");
    }
}
