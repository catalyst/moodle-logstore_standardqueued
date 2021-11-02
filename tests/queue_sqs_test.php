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
 * Standard log store tests.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use logstore_standardqueued\queue\sqs;

/**
 * Standard log store tests.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logstore_standardqueued_queue_sqs_testcase extends advanced_testcase {
    /**
     * Create sqs que for test
     *
     */
    private function create_queue() {
        $queueurl = 'url';
        $client = $this->getMockBuilder(curl::class)
            ->setMethods(['post'])
            ->disableOriginalConstructor()
            ->getMock();

        $sqs = new t_sqs($queueurl, $client);
        $this->assertSame("sqs $queueurl", $sqs->details());
        $this->assertTrue($sqs->is_configured());

        return $sqs;
    }

    /**
     * JSON encode helper
     *
     * @param array $data to encode
     * @return string
     */
    private function json_encode(array $data) {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /**
     * JSON decode helper
     *
     * @param string $json encoded
     * @return array
     */
    private function json_decode($json) {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * curl options helper
     *
     * @param int $timeout
     * @return array
     */
    private function curl_options($timeout) {
        return [
            'CURLOPT_CONNECTTIMEOUT' => $timeout,
            'CURLOPT_HTTPHEADER' => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ];
    }

    /**
     * Tests push_entry
     *
     */
    public function test_push_entry() {
        $this->resetAfterTest();

        $sqs = $this->create_queue();

        $evententry = ['a' => "b"];
        $sqs->client->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo($sqs->schedule_action_url('SendMessage')),
                $this->callback(function($json) use ($sqs, $evententry) {
                    $this->assertEquals([
                        'QueueUrl' => $sqs->queueurl,
                        'MessageBody' => $this->json_encode($evententry)
                    ], $this->json_decode($json));
                    return true;
                }),
                $this->equalTo($this->curl_options(1))
            )
            ->willReturnCallback(function($endpoint, $json, $options) use ($sqs) {
                $sqs->client->info = [
                    'http_code' => 204,
                ];
            });
        $sqs->push_entry($evententry);
    }

    /**
     * Tests pull_entries
     *
     */
    public function test_pull_entries() {
        $this->resetAfterTest();

        $sqs = $this->create_queue();

        $messagereceipthandle = 'ReceiptHandle';
        $evententry = ['a' => "b"];
        $curloptions = $this->curl_options(3);
        $sqs->client->expects($this->exactly(3))
            ->method('post')
            ->withConsecutive(
                [
                    $this->equalTo($sqs->request_action_url('ReceiveMessage')),
                    $this->callback(function($json) use ($sqs, $evententry) {
                        $this->assertEquals([
                            'QueueUrl' => $sqs->queueurl,
                            'MaxNumberOfMessages' => 10,
                        ], $this->json_decode($json));
                        return true;
                    }),
                    $this->equalTo($curloptions)
                ],
                [
                    $this->equalTo($sqs->request_action_url('DeleteMessage')),
                    $this->callback(function($json) use ($sqs, $messagereceipthandle, $evententry) {
                        $this->assertEquals([
                            'QueueUrl' => $sqs->queueurl,
                            'ReceiptHandle' => $messagereceipthandle,
                        ], $this->json_decode($json));
                        return true;
                    }),
                    $this->equalTo($curloptions)
                ],
                [
                    $this->equalTo($sqs->request_action_url('ReceiveMessage')),
                    $this->callback(function($json) use ($sqs, $evententry) {
                        $this->assertEquals([
                            'QueueUrl' => $sqs->queueurl,
                            'MaxNumberOfMessages' => 10,
                        ], $this->json_decode($json));
                        return true;
                    }),
                    $this->equalTo($curloptions)
                ],
            )
            ->willReturnCallback(function($endpoint, $json, $options) use ($sqs, $messagereceipthandle, $evententry) {
                if (strpos($endpoint, 'ReceiveMessage') !== false) {
                    static $first = true;

                    $sqs->client->info = [
                        'http_code' => 200,
                    ];

                    if ($first) {
                        $first = false;
                        $messageid = 'Id';
                        return $this->json_encode([
                            'Messages' => [
                                [
                                    'MessageId' => $messageid,
                                    'ReceiptHandle' => $messagereceipthandle,
                                    'Body' => $this->json_encode($evententry)
                                ],
                            ]
                        ]);
                    }

                    return "{}";
                }

                if (strpos($endpoint, 'DeleteMessage') !== false) {
                    $sqs->client->info = [
                        'http_code' => 204,
                    ];
                    return "";
                }

                throw new Exception("Unexpected call $endpoint");
            });
        $this->assertSame([$evententry], $sqs->pull_entries());
    }

    /**
     * Tests is_configured
     *
     */
    public function test_is_configured() {
        $sqs = new sqs(null, null);
        $this->assertFalse($sqs->is_configured());
        $this->assertFalse($sqs->is_operational());
    }

    /**
     * Tests pull_entries
     *
     */
    public function test_is_operational() {
        $this->resetAfterTest();

        $sqs = $this->create_queue();

        $sqs->client->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo($sqs->request_action_url('ReceiveMessage')),
                $this->callback(function($json) use ($sqs) {
                    $this->assertEquals([
                        'QueueUrl' => $sqs->queueurl,
                        'MaxNumberOfMessages' => 1,
                        'VisibilityTimeout' => 0,
                    ], $this->json_decode($json));
                    return true;
                }),
                $this->equalTo($this->curl_options(3))
            )
            ->willReturnCallback(function($endpoint, $json, $options) use ($sqs) {
                $sqs->client->info = [
                    'http_code' => 200,
                ];
                return $this->json_encode([
                    'Messages' => []
                ]);
            });
        $this->assertTrue($sqs->is_operational());
    }
}

/**
 * SQS queue test classs.
 *
 * @package    logstore_standardqueued
 * @author     Srdjan Janković
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class t_sqs extends sqs {
    /** @var string $queueurl AWS SQS queue url */
    public $queueurl;

    /** @var string $proxy url */
    public $proxy = "https//my.aws.proxy/";

    /** @var curl $client curl client */
    public $client;

    /**
     * Constructor.
     *
     * @param string $queueurl
     * @param curl $client mock curl client
     */
    public function __construct($queueurl, $client) {
        $this->queueurl = $queueurl;
        $this->client = $client;
    }

    /**
     * Return new curl client
     *
     * @return curl
     */
    public function curl() {
        return $this->client;
    }

    /**
     * Request action url test helper
     *
     * @param string $action SQS API action
     * @return string
     */
    public function request_action_url($action) {
        return $this->proxy."/do/sqs/$action";
    }

    /**
     * Schedule action url test helper
     *
     * @param string $action SQS API action
     * @return string
     */
    public function schedule_action_url($action) {
        return $this->proxy."/schedule/sqs/$action";
    }
}
