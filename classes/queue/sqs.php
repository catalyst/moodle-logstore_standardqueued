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
 *
 * Standard log queue
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_standardqueued\queue;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/filelib.php';

use Exception, JsonException;
use moodle_exception;
use curl;

/**
 * Standard log queue
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sqs implements queue_interface
{
    // @var string $queueurl AWS SQS queue url
    protected $queueurl;

    // @var string $proxy base url of a proxy service that will accept requests
    protected $proxy;

    /**
     * Constructor.
     *
     * @param string $queuename
     * @param string $queueendpoint
     */
    public function __construct($queuename, $queueendpoint)
    {
        $this->queueurl = $queuename;
        $this->proxy = $queueendpoint;
    }

    /**
     * Return new curl client
     *
     * @return curl
     */
    protected function curl()
    {
        return new curl(['proxy' => false, 'ignoresecurity' => true]);
    }

    /**
     * JSON encode helper
     *
     * @param array $data to encode
     *
     * @return string
     */
    private function json_encode(array $data)
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /**
     * JSON decode helper
     *
     * @param string $json encoded
     *
     * @return array
     */
    private function json_decode($json)
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new JsonException($json, 0, $e);
        }
    }

    /**
     * Make a SQS proxy schedule request
     *
     * @param string $action SQS API action
     * @param array  $params SQS API action params without queue spec
     *
     * @throws moodle_exception
     */
    protected function schedule($action, array $params)
    {
        $this->do_request($action, $params, 'schedule', 1);
    }

    /**
     * Make a SQS proxy immediate request
     *
     * @param string $action SQS API action
     * @param array  $params SQS API action params without queue spec
     *
     * @return array
     * @throws moodle_exception
     */
    protected function request($action, array $params)
    {
        $ret = $this->do_request($action, $params, 'do');
        return $ret === null ? null : $this->json_decode($ret);
    }

    /**
     * Make a SQS proxy request
     *
     * @param string $action  SQS API action
     * @param array  $params  SQS API action params without queue spec
     * @param string $slug    SQS API path slug
     * @param int    $timeout connect timeout, default 3
     *
     * @return string
     * @throws moodle_exception
     */
    private function do_request($action, array $params, $slug, $timeout = 3)
    {
        $params['QueueUrl'] = $this->queueurl;

        $client = $this->curl();
        $ret = $client->post(
            $this->proxy."/$slug/sqs/$action",
            $this->json_encode($params),
            [
                'CURLOPT_HTTPHEADER' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
                'CURLOPT_CONNECTTIMEOUT' => $timeout,
            ]
        );
        $code = $client->info['http_code'];
        if (!$code || $code >= 300) {
            throw new moodle_exception("schedule $action: $code $ret");
        }
        return $code === 204 ? null : $ret;
    }

    /**
     * A line describing the queue and its config.
     *
     * @return string
     */
    public function details()
    {
        return "sqs ".$this->queueurl;
    }

    /**
     * Push the event to the queue.
     *
     * @param array $evententry raw event data
     */
    public function push_entry(array $evententry)
    {
        $this->schedule(
            'SendMessage',
            ['MessageBody' => $this->json_encode($evententry)]
        );
    }

    /**
     * Push the events to the queue.
     *
     * @param array $evententries raw event data
     */
    public function push_entries(array $evententries)
    {
        $entries = [];
        foreach ($evententries as $id => $entry) {
            $entries[] = [
                'Id' => $id,
                'MessageBody' => $this->json_encode($entry)
            ];
        }

        $this->schedule(
            'SendMessageBatch',
            ['Entries' => $entries]
        );
    }

    /**
     * Pull the events from the queue.
     *
     * @param int $num max number of events to pull
     */
    public function pull_entries($num=null)
    {
        $awsmax = 10;  // Max messages that AWS is willing to return in one go.
        $max = $num && $num < $awsmax ? $num : $awsmax;

        $pulled = [];
        while (true) {
            $res = $this->request(
                'ReceiveMessage',
                ['MaxNumberOfMessages' => $max]
            );

            $msgs = $res['Messages'] ?? null;
            if (!$msgs || count($msgs) == 0) {
                break;
            }

            foreach ($msgs as $msg) {
                $body = $msg['Body'];
                $id = $msg['MessageId'];
                $rh = $msg['ReceiptHandle'];

                try {
                    $this->request(
                        'DeleteMessage',
                        ['ReceiptHandle' => $rh]
                    );
                } catch (Exception $e) {
                    debugging(
                        "logstore_standardqueued: Failed to delete message: $body\n".
                        $e->getMessage()
                    );
                    continue;
                }

                try {
                    $pulled[] = $this->json_decode($body);
                } catch (JsonException $e) {
                    debugging(
                        "logstore_standardqueued: Message decode error: $body\n".
                        $e->getMessage()
                    );
                    // Nothing we can do about it at this stage.
                    continue;
                }
            }

            if ($num && count($pulled) >= $num) {
                break;
            }
        }
        return $pulled;
    }

    /**
     * Did we configure this queue?
     *
     * @return bool
     */
    public function is_configured()
    {
        return $this->queueurl && $this->proxy;
    }

    /**
     * Can we use this queue?
     *
     * @return bool
     */
    public function is_operational()
    {
        if ($this->is_configured()) {
            // Test the queue.
            $this->request(
                'ReceiveMessage',
                ['MaxNumberOfMessages' => 1, 'VisibilityTimeout' => 0]
            );
            return true;
        }
        return false;
    }
}
