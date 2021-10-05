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
 * Standard log queue
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_standardqueued\queue;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');
use Aws\Sqs\SqsClient, Aws\Exception\AwsException;

use Exception, JsonException;

/**
 * Standard log queue
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sqs implements queue_interface {
    /** @var array $deps list of dependancies */
    public static $deps = ['aws'];

    /** @var string $configerror set in case of misconfiguration */
    public $configerror;

    /** @var string $queueurl AWS SQS queue url */
    protected $queueurl;

    /** @var SqsClient $client AWS SQS api client */
    protected $client;

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;

        if (!isset($CFG->logstore_standardqueued['sqs'])) {
            return;
        }

        $config = $CFG->logstore_standardqueued['sqs'];

        if (!isset($config['queue_url'])) {
            $this->configerror = "No queue_url in config";
            return;
        }

        $this->queueurl = $config['queue_url'];
        try {
            // Setup client params and instantiate client.
            $params = [
                'version' => 'latest',
                'http' => ['proxy' => \local_aws\local\aws_helper::get_proxy_string()],
            ];
            if (isset($config['aws_region'])) {
                $params['region'] = $config['aws_region'];
            }
            if (isset($config['aws_key'])) {
                $params['credentials'] = [
                    'key' => $config['aws_key'],
                    'secret' => $config['aws_secret']
                ];
            }

            $this->client = new SqsClient($params);
        } catch (AwsException $e) {
            $this->configerror = $e->getAwsErrorMessage();
        } catch (Exception $e) {
            $this->configerror = $e->getMessage();
        }
    }

    /**
     * A line describing the queue and its config.
     *
     * @return string
     */
    public function details() {
        return "sqs ".$this->queueurl;
    }

    /**
     * Push the event to the queue.
     *
     * @param array $evententry raw event data
     */
    public function push_entry(array $evententry) {
        $this->client->sendMessage([
            'QueueUrl' => $this->queueurl,
            'MessageBody' => json_encode(
                $evententry,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
        ]);
    }

    /**
     * Push the events to the queue.
     *
     * @param array $evententries raw event data
     */
    public function push_entries(array $evententries) {
        $entries = [];
        foreach ($evententries as $id => $entry) {
            $entries[] = [
                'Id' => $id,
                'MessageBody' => json_encode(
                    $entry,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
            ];
        }

        $res = $this->client->sendMessageBatch([
            'QueueUrl' => $this->queueurl,
            'Entries' => $entries,
        ]);

        if ($res['Failed']) {
            var_dump($res['Failed']);
            throw new Exception("Failed");
        }
    }

    /**
     * Pull the events from the queue.
     *
     * @param int $num max number of events to pull
     */
    public function pull_entries($num=null) {
        $awsmax = 10;  // Max messages that AWS is willing to return in one go.
        $max = $num && $num < $awsmax ? $num : $awsmax;

        $pulled = [];
        while (true) {
            $res = $this->client->receiveMessage([
                'QueueUrl' => $this->queueurl,
                'MaxNumberOfMessages' => $max,
            ]);

            $msgs = $res['Messages'];
            if (!$msgs || count($msgs) == 0) {
                break;
            }

            foreach ($msgs as $msg) {
                $body = $msg['Body'];
                $id = $msg['MessageId'];
                $rh = $msg['ReceiptHandle'];

                try {
                    $this->client->deleteMessage([
                        'QueueUrl' => $this->queueurl,
                        'ReceiptHandle' => $rh,
                    ]);
                } catch (AwsException $e) {
                    debugging(
                        "logstore_standardqueued: Failed to delete message: $body\n".
                        $e->getAwsErrorMessage()
                    );
                    continue;
                }

                try {
                    $pulled[] = json_decode($body,  JSON_THROW_ON_ERROR);
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
    public function is_configured() {
        return isset($this->client);
    }

    /**
     * Can we use this queue?
     *
     * @return bool
     */
    public function is_operational() {
        try {
            // Test the queue.
            $this->client->receiveMessage([
                'QueueUrl' => $this->queueurl,
                'MaxNumberOfMessages' => 1,
                'VisibilityTimeout' => 0,
            ]);
            return true;
        } catch (AwsException $e) {
            $this->configerror = $e->getAwsErrorMessage();
        } catch (Exception $e) {
            $this->configerror = $e->getMessage();
        }
    }
}
