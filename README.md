![Build Status](https://github.com/catalyst/moodle-logstore_standardqueued/actions/workflows/ci.yml/badge.svg?branch=main)

# moodle-logstore_standardqueued

This is a Moodle logstore which queues logs via an intermediate fast queue and
then later these will be eventually put back into the same tables as the
standard log so it behaves exactly as it normally does.

## Branches

A minimum of PHP 7.3 is required to run this plugin.

| Moodle Version    |  Branch          |
|-------------------|------------------|
| Moodle 3.9++      | MOODLE_39_STABLE |


## What is great about the standard log store?

It works just out of the box, and many plugins pull data from it. Because it is
just a local database table it needs no special extra database connection.

## What is less that great with the standard log store?

In a very large scale Moodle there might be 30 front ends across 3 data centers,
and lets assume you are also using the database primary / replica feature in
Moodle. In this setup there will read DB replicas in each data center. In a
typical case any given http request to moodle typically make a lot of read
queries and then at the end of the process does a single DB write for a log
entry. By doing a write it then has to create a new database connection to the
primary database for a single query. We would like eliminate this connection
and write as much as possible.

## How are events queued?

A DB connection and write is already fairly fast and cheap so whatever it is
eplaced with must be extremely low latency and cheaper while balancing that
again reliability and robustness. This plugin will flexibly allow different
queue methods. The intention is that a single front end might have a very short
lived buffer which is accepting events from many php requests, and then doing a
much faster single batch insert. Or it may push them to a more decentralized
buffer, one in each data center, which each pushes events back into the central
standard log eventually.

Each queue method will have its own means of both queueing the events and later
dequeueing them and batch inserting them.

## Configuration

Configuring is done in `config.php`:

```php
$CFG->logstore_standardqueued = [
    '<queue>' => [params...]
];
```

## Queues supported

### SQS

```php
$CFG->logstore_standardqueued = [
    'sqs' => [
        'queue_url' => 'https://sqs.ap-southeast-2.amazonaws.com/XXXX/some-queue',
        'proxy_url' => 'https://my.proxy.service',
    ]
];
```
