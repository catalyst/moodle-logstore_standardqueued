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
 * Standard log queue check
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace logstore_standardqueued\check;

defined('MOODLE_INTERNAL') || die();

use core\check\check,
    core\check\result;
use logstore_standardqueued\log\store;

/**
 * Standard log queue check
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue extends check {
    /**
     * Return check result
     * @return result
     */
    public function get_result(): result {
        $configuredqueue = store::configured_queue();
        $details = null;
        if ($configuredqueue) {
            if ($configuredqueue->is_operational()) {
                $status = result::OK;
                $summary = get_string('queue', 'logstore_standardqueued');
                $details = $configuredqueue->details();
            } else {
                $status = result::ERROR;
                $summary = get_string('notconfigured', 'logstore_standardqueued');
                $details = $configuredqueue->configerror;
            }
        } else {
            $status = result::ERROR;
            $summary = get_string('notconfigured', 'logstore_standardqueued');
            $details = implode("; ", store::$configerrors);
        }

        return new result($status, $summary, $details);
    }
}
