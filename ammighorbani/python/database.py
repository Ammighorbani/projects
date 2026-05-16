from pymongo import MongoClient

client = MongoClient("mongodb://mongodb:27017")

db = client["chat_app"]

messages = db["messages"]

