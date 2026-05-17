<?php

session_start();

$username = $_SESSION['username'];
$uuid = $_SESSION['uuid'];
$sender_id = $_SESSION['user_id'];

$receiver_id = $_GET['user'];

?>

<!DOCTYPE html>
<html>
<head>

<title>Chat</title>

<style>

body{
font-family: sans-serif;
}

#chat{
height:400px;
overflow-y:scroll;
border:1px solid #ccc;
padding:10px;
}

#input-area{
margin-top:10px;
}

</style>

</head>

<body>

<div id="chat"></div>

<div id="input-area">
<input id="messageInput" type="text">
<button onclick="sendMessage()">Send</button>
</div>

<script>

const username = "<?php echo $username; ?>";
const uuid = "<?php echo $uuid; ?>";
const sender_id = "<?php echo $sender_id; ?>";
const receiver_id = "<?php echo $receiver_id; ?>";

const chat_id =
Math.min(sender_id,receiver_id) + "_" +
Math.max(sender_id,receiver_id);

const chat = document.getElementById("chat");

function addMessage(user,msg){

    const div = document.createElement("div");

        div.innerHTML = "<b>"+user+"</b>: "+msg;

            chat.appendChild(div);

                chat.scrollTop = chat.scrollHeight;
                }

                async function loadMessages(){

                    const res = await fetch(
                            "http://192.168.107.160:8000/messages/"+chat_id
                                );

                                    const data = await res.json();

                                        data.forEach(m=>{
                                                addMessage(m.user,m.message);
                                                    });

                                                    }

                                                    loadMessages();

                                                    const ws = new WebSocket(
                                                    "ws://192.168.107.160:8000/ws/"+receiver_id
                                                    );

                                                    ws.onmessage = function(event){

                                                        const data = JSON.parse(event.data);

                                                            addMessage(data.user,data.message);

                                                            };

                                                            function sendMessage(){

                                                                const input = document.getElementById("messageInput");

                                                                    const data = {

                                                                            user: username,
                                                                                    uuid: uuid,
                                                                                            sender_id: sender_id,
                                                                                                    receiver_id: receiver_id,
                                                                                                            message: input.value

                                                                                                                };

                                                                                                                    ws.send(JSON.stringify(data));

                                                                                                                        input.value="";

                                                                                                                        }
                                                                                                                        console.log("sender_id:", sender_id);
															console.log("receiver_id:", receiver_id);
															console.log("chat_id:", chat_id);

                                                                                                                        </script>

                                                                                                                        </body>
                                                                                                                        </html>

