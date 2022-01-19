<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('drupal_test', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, "http://localhost:8888/drupal/api/upload_mp3");
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    "nid=" . $msg->body
  );

  // In real life you should use something like:
  // curl_setopt($ch, CURLOPT_POSTFIELDS, 
  //          http_build_query(array('postvar1' => 'value1')));

  // Receive server response ...
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $server_output = curl_exec($ch);

  curl_close($ch);
  
  print_r($server_output); 
  echo ' [x] Received nid: ', $msg->body, "\n";
};

$channel->basic_consume('drupal_test', '', false, true, false, false, $callback);

while ($channel->is_open()) {
  $channel->wait();
}

$channel->close();
$connection->close();
