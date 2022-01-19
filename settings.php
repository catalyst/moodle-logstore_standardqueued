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
    // Check if we are actually on the settings page, or in correct category page.
    $caturl = new moodle_url('/admin/category.php');
    $pageurl = new moodle_url('/admin/settings.php');
    $onsettingspage = false;

    if ($PAGE->has_set_url()) {
        $thisurl = $PAGE->url;
        if (($caturl->compare($thisurl, URL_MATCH_BASE) && $thisurl->get_param('category') == 'logging') ||
            ($pageurl->compare($thisurl,  URL_MATCH_BASE) && $thisurl->get_param('section') == 'logsettingstandardqueued')) {
            $onsettingspage = true;
        }
    }

    $warntext = '';

    if (store::both_logstore_standard_enabled()) {
        $warntext  = $OUTPUT->notification(
            get_string('bothconfigured', 'logstore_standardqueued'),
            core\output\notification::NOTIFY_ERROR
        );

        $settings->add(new admin_setting_heading('logstore_standardqueued/bothconfigured', '', $warntext));
    }

    if ($onsettingspage) {
        $configuredqueue = store::configured_queue();

        if (!$configuredqueue) {
            $warntext = $OUTPUT->notification(
                get_string('queuenotconfigured', 'logstore_standardqueued'),
                core\output\notification::NOTIFY_WARNING
            );
        } else {
            $url = new moodle_url(queue::$detailspath);

            try {
                $isoperational = $configuredqueue->is_operational();
            } catch (Exception $e) {
                $isoperational = false;
            }

            if (!$isoperational) {
                $statuslink = html_writer::link($url, get_string('statuspage', 'logstore_standardqueued'));
                $warntext = $OUTPUT->notification(
                    get_string('queuenotfunctional', 'logstore_standardqueued', $statuslink),
                    core\output\notification::NOTIFY_ERROR
                );
            } else {
                $warntext = $OUTPUT->notification(
                    get_string('queuetested', 'logstore_standardqueued', $configuredqueue->details()),
                    core\output\notification::NOTIFY_SUCCESS
                );
            }
        }

        $settings->add(new admin_setting_heading('logstore_standardqueued/queuestatus', '', $warntext));
    }

    $settings->add(new admin_setting_configselect(
        'logstore_standardqueued/queuetype',
        new lang_string('queuetype', 'logstore_standardqueued'),
        new lang_string('queuetype_desc', 'logstore_standardqueued'),
        null, array_combine(store::$queueclasses, store::$queueclasses)
    ));
    $settings->add(new admin_setting_configtext(
        'logstore_standardqueued/queuename',
        new lang_string('queuename', 'logstore_standardqueued'),
        new lang_string('queuename_desc', 'logstore_standardqueued'),
        null
    ));
    $settings->add(new admin_setting_configtext(
        'logstore_standardqueued/queueendpoint',
        new lang_string('queueendpoint', 'logstore_standardqueued'),
        new lang_string('queueendpoint_desc', 'logstore_standardqueued'),
        null
    ));

    require(__DIR__ . "/../standard/settings.php");
}
