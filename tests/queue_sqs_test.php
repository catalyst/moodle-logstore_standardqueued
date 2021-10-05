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
    public function create_queue() {
        $queueurl = 'url';
        $client = $this->getMockBuilder(Aws\Sqs\SqsClient::class)
            ->setMethods(['sendMessage', 'receiveMessage', 'deleteMessage'])
            ->disableOriginalConstructor()
            ->getMock();

        $sqs = new t_sqs($queueurl, $client);
        $this->assertSame("sqs $queueurl", $sqs->details());
        $this->assertTrue($sqs->is_configured());

        return $sqs;
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
            ->method('sendMessage')
            ->with($this->equalTo([
                'QueueUrl' => $sqs->queueurl,
                'MessageBody' => json_encode(
                    $evententry,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
            ]));
        $sqs->push_entry($evententry);
    }

    /**
     * Tests pull_entries
     *
     */
    public function test_pull_entries() {
        $this->resetAfterTest();

        $sqs = $this->create_queue();

        $messageid = 'Id';
        $messagereceipthandle = 'ReceiptHandle';
        $evententry = ['a' => "b"];
        $sqs->client->expects($this->exactly(2))
            ->method('receiveMessage')
            ->with($this->equalTo([
                'QueueUrl' => $sqs->queueurl,
                'MaxNumberOfMessages' => 10,
            ]))
            ->willReturnOnConsecutiveCalls(
                [
                    'Messages' => [
                        [
                            'MessageId' => $messageid,
                            'ReceiptHandle' => $messagereceipthandle,
                            'Body' => json_encode(
                                $evententry,
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                            ),
                        ],
                    ]
                ],
                [
                    'Messages' => []
                ],
            );
        $sqs->client->expects($this->once())
            ->method('deleteMessage')
            ->with($this->equalTo([
                'QueueUrl' => $sqs->queueurl,
                'ReceiptHandle' => $messagereceipthandle,
            ]));
        $this->assertSame([$evententry], $sqs->pull_entries());
    }

    /**
     * Tests pull_entries
     *
     */
    public function test_is_operational() {
        $this->resetAfterTest();

        $sqs = $this->create_queue();

        $sqs->client->expects($this->once())
            ->method('receiveMessage')
            ->with($this->equalTo([
                'QueueUrl' => $sqs->queueurl,
                'MaxNumberOfMessages' => 1,
                'VisibilityTimeout' => 0,
            ]))
            ->willReturn([
                'Messages' => [],
            ]);
        $res = $sqs->is_operational();
        if ($sqs->configerror) {
            throw new Exception($sqs->configerror);
        }
        $this->assertTrue($res);
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


    /** @var SqsClient $client AWS SQS api client */
    public $client;

    /**
     * Constructor.
     */
    public function __construct($queueurl, $client) {
        $this->queueurl = $queueurl;
        $this->client = $client;
    }
}
