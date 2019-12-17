<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use SMSPLANET\PHP\Client;

$dotenv = Dotenv::create(__DIR__);
$dotenv->load();

$client = new Client([
    'key' => getenv('API_KEY'),
    'password' => getenv('API_PASSWORD')
]);

// Simple SMS

//var_dump($message_id = $client->sendSimpleSMS([
//    'to' => [getenv('TEST_NUMBER')],
//    'from' => 'TEST',
//    'msg' => 'Test Simple SMS'
//]));
//
//var_dump(
//    $client->getMessageStatus($message_id)
//);

// SMS

//var_dump($message_id = $client->sendSMS([
//    'to' => [getenv('TEST_NUMBER')],
//    'from' => 'TEST',
//    'msg' => 'Test Simple SMS'
//]));
//
//var_dump(
//    $client->getMessageStatus($message_id)
//);

// MMS

var_dump($message_id = $client->sendMMS([
    'to' => [getenv('TEST_NUMBER')],
    'from' => 'TEST',
    'msg' => 'Test Simple SMS'
]));

var_dump(
    $client->getMessageStatus($message_id)
);
