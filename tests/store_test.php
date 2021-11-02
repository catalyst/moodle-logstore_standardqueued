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

 * Standard log store tests.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../../standard/tests/fixtures/event.php';
require_once __DIR__ . '/fixtures/store.php';

use logstore_standardqueued\log\store;

/**
 * Standard log store tests.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logstore_standardqueued_store_testcase extends advanced_testcase
{
    // @var string Original error log
    protected $oldlog;

    /**
     * Tests configured_queue()
     */
    public function test_configured_queue()
    {
        $this->resetAfterTest();

        set_config('queuetype', null, 'logstore_standardqueued');
        $this->assertNull(store::configured_queue());

        set_config('queuetype', 'sqs', 'logstore_standardqueued');
        set_config('queuename', null, 'logstore_standardqueued');
        $this->assertNull(store::configured_queue());

        set_config('queuename', 'whatever', 'logstore_standardqueued');
        set_config('queueendpoint', 'whatever', 'logstore_standardqueued');
        $this->assertNotNull(store::configured_queue());
    }

    /**
     * Tests queued log not used when both logstore_standardqueued
     * and logstore_standard are enabled.
     *
     * @throws moodle_exception
     */
    public function test_both_plugins_enabled()
    {
        $this->resetAfterTest();

        // Enable both logging plugins.
        set_config(
            'enabled_stores',
            'logstore_standard,logstore_standardqueued',
            'tool_log'
        );
        $manager = get_log_manager(true);

        $stores = $manager->get_readers();
        $this->assertCount(2, $stores);
        // @var \logstore_standard\log\store $store
        $store = $stores['logstore_standardqueued'];
        $this->assertInstanceOf('tool_log\log\writer', $store);
        $this->assertFalse($store->is_logging());

        // Enable both logging plugins.
        set_config('enabled_stores', 'logstore_standardqueued', 'tool_log');
        $manager = get_log_manager(true);

        $stores = $manager->get_readers();
        $this->assertCount(1, $stores);
        // @var \logstore_standard\log\store $store
        $store = $stores['logstore_standardqueued'];
        $this->assertInstanceOf('tool_log\log\writer', $store);
        $this->assertTrue($store->is_logging());
    }

    /**
     * Tests queued log writing with a well behaved queue.
     *
     * @throws moodle_exception
     */
    public function test_queued_log_writing_good_queue()
    {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.

        \logstore_standardqueued_test\log\store::$bad = false;

        // Apply JSON format system setting.
        set_config('jsonformat', 1, 'logstore_standard');
        set_config('buffersize', 0, 'logstore_standard');
        set_config('logguests', 1, 'logstore_standard');

        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $module1 = $this->getDataGenerator()->create_module(
            'resource', ['course' => $course1]
        );
        $course2 = $this->getDataGenerator()->create_course();
        $module2 = $this->getDataGenerator()->create_module(
            'resource', ['course' => $course2]
        );

        // Enable logging plugin.
        set_config('enabled_stores', 'logstore_standardqueued_test', 'tool_log');
        $manager = get_log_manager(true);

        $stores = $manager->get_readers();
        $this->assertCount(1, $stores);
        $this->assertEquals(
            ['logstore_standardqueued_test'],
            array_keys($stores)
        );
        // @var \logstore_standard\log\store $store
        $store = $stores['logstore_standardqueued_test'];
        $this->assertInstanceOf('tool_log\log\writer', $store);
        $this->assertTrue($store->is_logging());

        $logs = $DB->get_records('logstore_standard_log', array(), 'id ASC');
        $this->assertCount(0, $logs);

        $this->setCurrentTimeStart();

        $this->setUser(0);
        $event1 = \logstore_standard\event\unittest_executed::create(
            [
                'context' => context_module::instance($module1->cmid),
                'other' => ['sample' => 5, 'xx' => 10],
            ]
        );
        $event1->trigger();

        $logs = $DB->get_records('logstore_standard_log', array(), 'id ASC');
        $this->assertCount(0, $logs);

        // Verbatim from \logstore_standardqueued\task\pull_task::execute().
        $store = new \logstore_standardqueued_test\log\store($manager);
        $store->store_queued_event_entries();

        $this->assertEquals(1, $DB->count_records('logstore_standard_log'));
    }

    /**
     * Tests queued log writing with a misbehaving queue.
     *
     * @throws moodle_exception
     */
    public function test_queued_log_writing_bad_queue()
    {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.

        \logstore_standardqueued_test\log\store::$bad = true;

        // Apply JSON format system setting.
        set_config('jsonformat', 1, 'logstore_standard');
        set_config('buffersize', 0, 'logstore_standard');
        set_config('logguests', 1, 'logstore_standard');

        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $module1 = $this->getDataGenerator()->create_module(
            'resource', ['course' => $course1]
        );
        $course2 = $this->getDataGenerator()->create_course();
        $module2 = $this->getDataGenerator()->create_module(
            'resource', ['course' => $course2]
        );

        // Enable logging plugin.
        set_config('enabled_stores', 'logstore_standardqueued_test', 'tool_log');
        $manager = get_log_manager(true);

        $stores = $manager->get_readers();
        $this->assertCount(1, $stores);
        $this->assertEquals(
            ['logstore_standardqueued_test'],
            array_keys($stores)
        );
        // @var \logstore_standard\log\store $store
        $store = $stores['logstore_standardqueued_test'];
        $this->assertInstanceOf('tool_log\log\writer', $store);
        $this->assertTrue($store->is_logging());

        $logs = $DB->get_records('logstore_standard_log', [], 'id ASC');
        $this->assertCount(0, $logs);

        $this->setCurrentTimeStart();

        $this->setUser(0);
        $event1 = \logstore_standard\event\unittest_executed::create(
            [
                'context' => context_module::instance($module1->cmid),
                'other' => ['sample' => 5, 'xx' => 10],
            ]
        );

        $event1->trigger();

        $this->assertDebuggingCalled(
            "logstore_standardqueued: Failed to push event to the queue: ".$store->exception_message()
        );

        $logs = $DB->get_records('logstore_standard_log', [], 'id ASC');
        $this->assertCount(1, $logs);
    }
}
