from fastapi import FastAPI

from app.admin import router as admin_router

app = FastAPI(title="FaceAuth Core")
app.include_router(admin_router)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/api/session/start")
def start_session_placeholder() -> dict[str, str]:
    """Keep the existing session-start path mounted for deployments that probe it."""
    return {"status": "not_configured"}
