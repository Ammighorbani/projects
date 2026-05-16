from pymongo import MongoClient

client = MongoClient("mongodb://127.0.0.1:27017")

db = client.chat_app

result = db.messages.insert_one({
        "sender_id": 1,
            "message": "سلام از پایتون",
            })

print("Inserted ID:", result.inserted_id)

