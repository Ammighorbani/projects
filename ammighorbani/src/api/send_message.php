<?php

session_start();
require "../vendor/autoload.php";

$client = new MongoDB\Client("mongodb://mongodb:27017");
$db = $client->messenger;
$messages = $db->messages;

$sender = $_SESSION['user_id'];
$receiver = intval($_POST['receiver_id']);
$text = trim($_POST['message']);

$conversation = $sender < $receiver 
    ? $sender . "_" . $receiver
    : $receiver . "_" . $sender;

$messages->insertOne([
    "conversation_id" => $conversation,
    "sender_id" => $sender,
    "receiver_id" => $receiver,
    "message" => $text,
    "type" => "text",
    "seen" => false,
    "created_at" => new MongoDB\BSON\UTCDateTime()
]);

echo json_encode(["status" => "ok"]);

