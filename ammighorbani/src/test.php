<?php
session_start();

if (!isset($_SESSION['is_logged']) || $_SESSION['is_logged'] !== true) {
	    header("Location: login.php");
	        exit;
}

$username = $_SESSION['username'];
$uuid = $_SESSION['uuid'];
$id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
<title>Chat Test</title>
</head>

<body>

<h2>Chat</h2>
<p>Logged in as: <?php echo htmlspecialchars($username); ?></p>

<input id="messageInput" type="text" placeholder="type message">
<button onclick="sendMessage()">Send</button>

<ul id="messages"></ul>

<script>

const username = "<?php echo $username; ?>";
const uuid = "<?php echo $uuid; ?>";
const sender_id = "<?php echo $id; ?>";

let receiver_id = 2
const ws = new WebSocket("ws://192.168.107.160:8000/ws/" + receiver_id);

ws.onmessage = function(event) {
	    const messages = document.getElementById("messages");
	        const li = document.createElement("li");
	        li.textContent = event.data;
		    messages.appendChild(li);
};

function sendMessage() {

	    const input = document.getElementById("messageInput");
	        
	    const data = {
	    	user: username,
		uuid: uuid,
		sender_id: sender_id,
		receiver_id: receiver_id,
		message: input.value
	    };

	        ws.send(JSON.stringify(data));

	        input.value = "";
}

</script>

</body>
</html>

