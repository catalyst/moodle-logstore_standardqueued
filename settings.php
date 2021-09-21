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
 * Standard log store settings.
 *
 * @package    logstore_standardqueued
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use logstore_standardqueued\log\store;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/../standard/settings.php");

if ($hassiteconfig) {
    if (store::both_logstore_standard_enabled()) {
        $warning = $OUTPUT->notification(get_string('bothconfigured', 'logstore_standardqueued'), 'notifyerror');
        $settings->add(new admin_setting_heading('logstore_standardqueued/bothconfigured', '', $warning));
    } else {
        $configuredqueue = store::configured_queue();
        if ($configuredqueue) {
            $info = $OUTPUT->notification(get_string('queue', 'logstore_standardqueued', $configuredqueue->details()), 'notify');
            $settings->add(new admin_setting_heading('logstore_standardqueued/queue', '', $info));

            foreach ($configuredqueue::$deps as $dep) {
                switch ($dep) {
                    case 'aws':
                        if (!file_exists($CFG->dirroot . '/local/aws/classes/admin_settings_aws_region.php')) {
                            $warning = $OUTPUT->notification(
                                get_string('awssdkrequired', 'logstore_standardqueued'),
                                'notifyerror'
                            );
                            $settings->add(new admin_setting_heading('logstore_standardqueued/awssdkwarning', '', $warning));
                        }
                        break;
                }
            }
        } else {
            $warning = $OUTPUT->notification(
                get_string('notconfigured', 'logstore_standardqueued', implode("; ", store::$configerrors)),
                'notifyerror'
            );
            $settings->add(new admin_setting_heading('logstore_standardqueued/notconfigured', '', $warning));
        }
    }
}
