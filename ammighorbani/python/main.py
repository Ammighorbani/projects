from fastapi.middleware.cors import CORSMiddleware
from fastapi import FastAPI, WebSocket
from bson import ObjectId
from database import messages
from datetime import datetime
import asyncio, json

app = FastAPI()

# Cors configurations
app.add_middleware(
        CORSMiddleware,
        allow_origins=["*"],
        allow_credentials=True,
        allow_methods=["*"],
        allow_headers=["*"]
)


connections = []


@app.get("/")
def home():
        return {"status": "chat server running"}

# Load messages in chat page
@app.get("/messages/{chat_id}")
def get_messages(chat_id: str):
    
    docs = messages.find({"chat_id": chat_id}).sort("timestamp", 1)

    result = []

    for d in docs:
        result.append({
            "sender_id": d["sender_id"],
            "receiver_id": d["receiver_id"],
            "message": d["message"],
            "user": d["user"],
            "timestamp": str(d["timestamp"])
        })

    return result


@app.websocket("/ws/{receiver_id}")
async def websocket_endpoint(websocket: WebSocket, receiver_id: str):

    await websocket.accept()
    connections.append(websocket)

    try:
        while True:

            data = await websocket.receive_text()

            # Parsing json
            payload = json.loads(data)

            sender_id = int(payload["sender_id"])
            receiver_id = int(payload["receiver_id"])

            chat_id = f"{min(sender_id, receiver_id)}_{max(sender_id, receiver_id)}"

            # Insert into database
            try:
                loop = asyncio.get_event_loop()

                # Insert data in another thread
                await loop.run_in_executor(None, lambda: messages.insert_one({

                    "chat_id": chat_id,
                    "sender_id": sender_id,
                    "receiver_id": receiver_id,
                    "user": payload["user"],
                    "uuid": payload["uuid"],
                    "message": payload["message"],
                    "timestamp": datetime.utcnow()

                }))

            except Exception as e:
                print(f"MongoDB error: {e}")


            # Brodcast to users
            for conn in connections:
                await conn.send_text(data)

    except:
        connections.remove(websocket)
