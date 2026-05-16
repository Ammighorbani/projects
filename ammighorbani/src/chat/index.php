<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Chat</title>
</head>

<body>

<div id="messages"></div>

<input id="message">
<button onclick="sendMessage()">Send</button>

<script>

let receiver = 5;

function loadMessages(){

	fetch("/api/get_messages.php?user_id="+receiver)
		.then(r=>r.json())
		.then(data=>{

		let html="";

		data.forEach(m=>{
		html += "<div>"+m.message+"</div>";
		});

		document.getElementById("messages").innerHTML=html;

});

}

function sendMessage(){

	let text=document.getElementById("message").value;

	fetch("/api/send_message.php",{
	method:"POST",
		headers:{"Content-Type":"application/x-www-form-urlencoded"},
		body:"receiver_id="+receiver+"&message="+encodeURIComponent(text)
	});

	document.getElementById("message").value="";
	loadMessages();

}

setInterval(loadMessages,2000);

loadMessages();

</script>

</body>
</html>

