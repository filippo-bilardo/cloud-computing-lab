"""
main.py — FbClaw Web Server
FastAPI con endpoint REST per chat testuale, trascrizione audio e chat vocale.
Avvia anche il bot Telegram in background se TELEGRAM_BOT_TOKEN è configurato.
"""
import os
import threading
from contextlib import asynccontextmanager

from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from fastapi.staticfiles import StaticFiles
from fastapi.responses import FileResponse
from pydantic import BaseModel
import uvicorn
from groq import Groq

from fastapi.responses import JSONResponse
from fastapi import Request

from agent import get_or_create_agent, reset_session as agent_reset_session, active_sessions

_groq_client = None

def get_groq_client() -> Groq:
    global _groq_client
    if _groq_client is None:
        api_key = os.getenv("GROQ_API_KEY")
        if not api_key:
            raise HTTPException(status_code=503, detail="GROQ_API_KEY non configurata nel file .env")
        _groq_client = Groq(api_key=api_key)
    return _groq_client


# ── Lifespan: avvia il bot Telegram in background ─────────────────────────────
@asynccontextmanager
async def lifespan(app: FastAPI):
    token = os.getenv("TELEGRAM_BOT_TOKEN", "")
    if token:
        from telegram_bot import start_bot_thread
        t = threading.Thread(target=start_bot_thread, daemon=True)
        t.start()
        print("[FbClaw] Bot Telegram avviato in background")
    else:
        print("[FbClaw] TELEGRAM_BOT_TOKEN non configurato — bot Telegram disabilitato")
    yield


# ── App ───────────────────────────────────────────────────────────────────────
app = FastAPI(title="FbClaw AI Agent", version="1.0.0", lifespan=lifespan)
app.mount("/static", StaticFiles(directory="static"), name="static")

@app.exception_handler(Exception)
async def global_exception_handler(request: Request, exc: Exception):
    return JSONResponse(status_code=500, content={"detail": str(exc)})


# ── Modelli Pydantic ──────────────────────────────────────────────────────────
class ChatRequest(BaseModel):
    message: str
    session_id: str = "web-default"


class ResetRequest(BaseModel):
    session_id: str


# ── Route ─────────────────────────────────────────────────────────────────────
@app.get("/")
async def root():
    return FileResponse("static/index.html")


@app.get("/api/status")
async def status():
    """Health check e informazioni sul servizio."""
    return {
        "status": "ok",
        "model": os.getenv("MISTRAL_MODEL", "mistral-large-latest"),
        "telegram_enabled": bool(os.getenv("TELEGRAM_BOT_TOKEN")),
        "active_sessions": active_sessions(),
    }


@app.post("/api/chat")
async def chat(req: ChatRequest):
    """
    Invia un messaggio testuale all'agente e restituisce la risposta.
    La cronologia viene mantenuta per session_id.
    """
    if not req.message.strip():
        raise HTTPException(status_code=400, detail="Il messaggio non può essere vuoto")

    try:
        agent = get_or_create_agent(req.session_id)
        response = agent.run(req.message, stream=False)
        content = response.content
        if not isinstance(content, str):
            content = str(content) if content is not None else ""
        return {"response": content, "session_id": req.session_id}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/transcribe")
async def transcribe(audio: UploadFile = File(...)):
    """
    Trascrive un file audio usando Groq Whisper.
    Restituisce solo il testo, senza inviarlo all'agente.
    """
    audio_bytes = await audio.read()
    try:
        client = get_groq_client()
        transcription = client.audio.transcriptions.create(
            file=(audio.filename or "audio.webm", audio_bytes),
            model="whisper-large-v3",
            response_format="text",
        )
        return {"text": transcription}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Errore trascrizione: {e}")


@app.post("/api/voice")
async def voice_to_agent(
    audio: UploadFile = File(...),
    session_id: str = Form(default="web-default"),
):
    """
    Pipeline completa: audio → trascrizione Groq → risposta agente.
    Restituisce sia il testo trascritto che la risposta dell'agente.
    """
    audio_bytes = await audio.read()

    # Step 1: Trascrizione con Groq Whisper
    try:
        client = get_groq_client()
        transcription = client.audio.transcriptions.create(
            file=(audio.filename or "audio.webm", audio_bytes),
            model="whisper-large-v3",
            response_format="text",
        )
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Errore trascrizione: {e}")

    # Step 2: Invio trascrizione all'agente
    try:
        agent = get_or_create_agent(session_id)
        response = agent.run(f"[Nota vocale trascrizione]: {transcription}", stream=False)
        content = response.content
        if not isinstance(content, str):
            content = str(content) if content is not None else ""
        return {
            "transcription": transcription,
            "response": content,
            "session_id": session_id,
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/reset")
async def reset(req: ResetRequest):
    """Azzera la cronologia della sessione specificata."""
    agent_reset_session(req.session_id)
    return {"status": "ok", "session_id": req.session_id}


# ── Entry point ───────────────────────────────────────────────────────────────
if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000, log_level="info")
