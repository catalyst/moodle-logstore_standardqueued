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
 * Settings
 *
 * @package     logqueue_sqs
 * @author      Srdjan Janković
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG, $OUTPUT;
if (file_exists($CFG->dirroot . '/local/aws/classes/admin_settings_aws_region.php')) {
    $info = $OUTPUT->notification(get_string('configinfo', 'logqueue_sqs'), 'notify');
    $settings->add(new admin_setting_heading('logqueue_sqs/configinfo', '', $info));

    $q = new \logqueue_sqs\queue();
    if (!$q->is_configured()) {
        $warning = $OUTPUT->notification(get_string('notconfigured', 'logqueue_sqs'), 'notifyerror');
        $settings->add(new admin_setting_heading('logqueue_sqs/notconfigured', '', $warning));
    }
} else {
    $warning = $OUTPUT->notification(get_string('awssdkrequired', 'logqueue_sqs'), 'notifyerror');
    $settings->add(new admin_setting_heading('logqueue_sqs/awssdkwarning', '', $warning));
}
