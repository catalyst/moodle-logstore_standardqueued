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

namespace logstore_standardqueued\check;

use core\check\check;
use core\check\result;
use action_link;
use moodle_url;
use logstore_standardqueued\log\store;

/**
 * Standard log enabled check
 *
 * @package    logstore_standardqueued
 * @author     Dmitrii Metelkin
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enabled extends check {

    /**
     * Is the check failed?
     * @return bool
     */
    private function is_check_failed(): bool {
        return store::both_logstore_standard_enabled();
    }

    /**
     * Return action link
     * @return ?action_link
     */
    public function get_action_link(): ?action_link {
        if ($this->is_check_failed()) {
            $url = new moodle_url('/admin/settings.php?section=managelogging');
            return new action_link($url, get_string('managelogging', 'tool_log'));
        }
        return null;
    }

    /**
     * Return check result
     * @return result
     */
    public function get_result(): result {
        if ($this->is_check_failed()) {
            $status = result::ERROR;
            $summary = get_string('bothconfigured', 'logstore_standardqueued');
        } else {
            $status = result::OK;
            $summary = get_string('enabledcorrectly', 'logstore_standardqueued');
        }

        return new result($status, $summary);
    }

}
