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

 * This command line script will test a provisioned Librelambda environment in AWS.
 *
 * @package     logstore_standardqueued
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

require __DIR__.'/../../../../../../config.php';
require_once $CFG->libdir.'/clilib.php';

$testevent = [
    'eventname' => "\\logstore_standardqueued\\event\\test",
    'component' => "logstore_standardqueued",
    'action' => "action",
    'target' => "target",
    'objecttable' => null,
    'objectid' => null,
    'crud' => "u",
    'edulevel' => 2,
    'contextid' => 159003,
    'contextlevel' => 70,
    'contextinstanceid' => "396000",
    'userid' => 0,
    'courseid' => "155000",
    'relateduserid' => null,
    'anonymous' => 0,
    'other' => "a:2:{s:6:\"sample\";i:5;s:2:\"xx\";i:10;}",
    'timecreated' => 1632116505,
    'origin' => "cli",
    'ip' => null,
    'realuserid' => null,
];

$q = new logstore_standardqueued\queue\sqs;
if (!$q->is_configured()) {
    die("logstore_standardqueued sqs not configured");
}

$now = microtime(true);
$q->push_entry($testevent);
echo "Push: ".(microtime(true) - $now)."\n";
sleep(2);
$entries = $q->pull_entries();

if (count($entries) != 1) {
    var_dump($entries);
} else {
    $ok = true;
    foreach ($testevent as $k => $v) {
        if (!array_key_exists($k, $entries[0])) {
            echo "$k: missing\n";
            $ok = false;
            var_dump($entries[0]);
            break;
        }

        $ev = $entries[0][$k];
        if ($ev != $v) {
            $ok = false;
            echo "$k: expected $v, got $ev\n";
        }
    }
    if ($ok) {
        echo "OK\n";
    }
}
