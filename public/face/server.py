from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import ollama

app = FastAPI()

# Разрешаем вашему файлу index.html отправлять запросы к этому серверу
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

class ChatRequest(BaseModel):
    message: str

@app.post("/ask")
def ask_ai(req: ChatRequest):
    response = ollama.chat(
        model='qwen2.5coder:3b',
        messages=[
            {'role': 'system', 'content': 'Ты ИИ-репетитор образовательной платформы EduMind. Отвечай кратко, понятно и на русском языке.'},
            {'role': 'user', 'content': req.message}
        ]
    )
    return {"reply": response['message']['content']}