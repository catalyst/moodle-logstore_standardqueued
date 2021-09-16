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
 * Standard log store tests.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../standard/tests/fixtures/event.php');
require_once(__DIR__ . '/fixtures/store.php');

class logstore_standardqueued_store_testcase extends advanced_testcase {
    /**
     * Tests log writing.
     *
     * @param bool $jsonformat True to test with JSON format
     * @throws moodle_exception
     */
    public function test_queued_log_writing_no_queue() {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.

        \logstore_standardqueued_test\log\test_queue::$configured = false;

        // Apply JSON format system setting.
        set_config('jsonformat', 1, 'logstore_standard');
        set_config('buffersize', 0, 'logstore_standard');
        set_config('logguests', 1, 'logstore_standard');

        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $module1 = $this->getDataGenerator()->create_module('resource', array('course' => $course1));
        $course2 = $this->getDataGenerator()->create_course();
        $module2 = $this->getDataGenerator()->create_module('resource', array('course' => $course2));

        // Enable logging plugin.
        set_config('enabled_stores', 'logstore_standardqueued_test', 'tool_log');
        $manager = get_log_manager(true);

        $stores = $manager->get_readers();
        $this->assertCount(1, $stores);
        $this->assertEquals(['logstore_standardqueued_test'], array_keys($stores));
        /** @var \logstore_standard\log\store $store */
        $store = $stores['logstore_standardqueued_test'];
        $this->assertInstanceOf('tool_log\log\writer', $store);
        $this->assertTrue($store->is_logging());

        $logs = $DB->get_records('logstore_standard_log', array(), 'id ASC');
        $this->assertCount(0, $logs);

        $this->setCurrentTimeStart();

        $this->setUser(0);
        $event1 = \logstore_standard\event\unittest_executed::create(
            array('context' => context_module::instance($module1->cmid), 'other' => array('sample' => 5, 'xx' => 10)));
        $event1->trigger();

        $logs = $DB->get_records('logstore_standard_log', array(), 'id ASC');
        $this->assertCount(1, $logs);
    }

    /**
     * Test that the standard log pull works correctly.
     */
    public function X_test_pull_task() {
        global $DB;

        $this->resetAfterTest();

        $pull = new \logstore_standardqueued\task\pull_task();
        $pull->execute();

        $this->assertEquals(1, $DB->count_records('logstore_standard_log'));
    }
}
