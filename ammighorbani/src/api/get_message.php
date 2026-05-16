<?php

session_start();
require "../vendor/autoload.php";

$client = new MongoDB\Client("mongodb://mongodb:27017");
$db = $client->messenger;
$messages = $db->messages;

$user = $_SESSION['user_id'];
$other = intval($_GET['user_id']);

$conversation = $user < $other 
	    ? $user . "_" . $other
	        : $other . "_" . $user;

$cursor = $messages->find(
	    ["conversation_id" => $conversation],
	        ["sort" => ["created_at" => 1]]
);

$result = [];

foreach ($cursor as $msg) {
	    $result[] = [
		            "sender_id" => $msg["sender_id"],
			            "message" => $msg["message"],
				            "time" => $msg["created_at"]
					        ];
}

echo json_encode($result);

