from fastapi import FastAPI
from api.verify import router as verify_router
from api.session import router as session_router
from api.license import router as license_router
from api.enroll import router as enroll_router

app = FastAPI(title="FaceAuth Core", version="0.1.0")

app.include_router(session_router)
app.include_router(verify_router)
app.include_router(license_router)
app.include_router(enroll_router)


@app.get("/health")
async def health():
    return {"status": "ok"}
