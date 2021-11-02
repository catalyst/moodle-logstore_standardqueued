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
use logstore_standardqueued\check\queue;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $warntext = '';

    if (store::both_logstore_standard_enabled()) {
        $warntext  = $OUTPUT->notification(
            get_string('bothconfigured', 'logstore_standardqueued'),
            core\output\notification::NOTIFY_WARNING
        );
    }

    $settings->add(new admin_setting_configselect(
        'logstore_standardqueued/queuetype',
        new lang_string('queuetype', 'logstore_standardqueued'),
        new lang_string('queuetype_desc', 'logstore_standardqueued'),
        0, array_combine(store::$queueclasses, store::$queueclasses)
    ));
    $settings->add(new admin_setting_configtext(
        'logstore_standardqueued/queuename',
        new lang_string('queuename', 'logstore_standardqueued'),
        new lang_string('queuename_desc', 'logstore_standardqueued'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'logstore_standardqueued/queueendpoint',
        new lang_string('queueendpoint', 'logstore_standardqueued'),
        new lang_string('queueendpoint_desc', 'logstore_standardqueued'),
        1
    ));

    $configuredqueue = store::configured_queue();
    if ($configuredqueue) {
        $url = new moodle_url(queue::$detailspath);
        $warntext = $OUTPUT->notification(
            get_string('queue', 'logstore_standardqueued', $configuredqueue->details())." ".html_writer::link($url, "Details"),
            core\output\notification::NOTIFY_SUCCESS
        );
        $settings->add(new admin_setting_heading('logstore_standardqueued/generalsettings', '', $warntext));
    } else {
        $warntext = $OUTPUT->notification(
            get_string('notconfigured', 'logstore_standardqueued'),
            core\output\notification::NOTIFY_WARNING
        );
        $settings->add(new admin_setting_heading('logstore_standardqueued/generalsettings', '', $warntext));

        require(__DIR__ . "/../standard/settings.php");
    }
}
