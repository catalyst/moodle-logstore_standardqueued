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
 * Log store lang strings.
 *
 * @package    logstore_standardqueued
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Standard log queued';
$string['pluginname_desc'] = 'Standard log with queueing middleware.';
$string['taskpull'] = 'Log table load from the queue';
$string['awssdkrequired'] = 'The local_aws plugin leveraging the AWS SDK is required to use this factor. Please install local_aws.';
$string['notconfigured'] = 'Queue details are not configured properly in the config file "logstore_standardqueued" array, queueing will not be used: {$a}';
$string['queue'] = 'Queue details: {$a}';
