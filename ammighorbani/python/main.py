from fastapi import FastAPI, WebSocket
from database import messages
import asyncio

app = FastAPI()

connections = []

@app.get("/")
def home():
        return {"status": "chat server running"}

@app.websocket("/ws/{chat_id}")
async def websocket_endpoint(websocket: WebSocket, chat_id: str):

    await websocket.accept()
    connections.append(websocket)

    try:
        while True:

            data = await websocket.receive_text()

            # Save in mongodb
            #messages.insert_one({
            #    "chat_id": chat_id,
            #    "message": data
            #})

            # Insert into database
            try:
                loop = asyncio.get_event_loop()

                # Insert data in another thread
                loop.run_in_executor(None, lambda: messages.insert_one({

                    "chat_id": chat_id,
                    "message": data

                }))

            except Exception as e:
                print(f"MongoDB error: {e}")


            # Brodcast to users
            for conn in connections:
                await conn.send_text(data)

    except:
        connections.remove(websocket)
