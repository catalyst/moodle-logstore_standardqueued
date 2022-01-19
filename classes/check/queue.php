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

use Exception;

use core\check\check,
    core\check\result;
use action_link;
use moodle_url;
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
    /** @var bool $is_operational whether the configured queue is operational */
    public static $detailspath = "/report/status/index.php?detail=logstore_standardqueued_queue";

    /** @var bool $is_operational whether the configured queue is operational */
    private $isoperational = false;

    /** @var string $queuedetails configured queue details */
    private $queuedetails;

    /** @var string $configerror */
    private $configerror = "";

    /**
     * Constructor
     */
    public function __construct() {
        $enabledlogstores = explode(',', get_config('tool_log', 'enabled_stores'));
        if (in_array('logstore_standardqueued', $enabledlogstores)) {
            if ($configuredqueue = store::configured_queue()) {
                $this->queuedetails = $configuredqueue->details();
                try {
                    $this->isoperational = $configuredqueue->is_operational();
                } catch (Exception $e) {
                    $this->configerror = "$e";
                }
            } else {
                $this->isoperational = false;
                $this->configerror = get_string('notconfigured', 'logstore_standardqueued');
            }
        } else {
            $this->isoperational = false;
            $this->configerror = get_string('notenabled', 'logstore_standardqueued');
        }
    }

    /**
     * Return action link
     * @return action_link
     */
    public function get_action_link(): ?action_link {
        if (!$this->isoperational) {
            $url = new moodle_url(self::$detailspath);
            return new action_link($url, get_string('configerror', 'logstore_standardqueued'));
        }
        return null;
    }

    /**
     * Return check result
     * @return result
     */
    public function get_result(): result {
        if ($this->isoperational) {
            $status = result::OK;
            $summary = get_string('queue', 'logstore_standardqueued', $this->queuedetails);
            $details = $this->queuedetails;
        } else {
            $status = result::ERROR;
            $summary = get_string('queuenotconfigured', 'logstore_standardqueued');
            $details = $this->queuedetails ? ($this->queuedetails."\n") : "";
            $details .= $this->configerror;
        }

        return new result($status, $summary, $details);
    }
}
