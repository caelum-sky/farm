# analytics-service/dependencies.py
"""Firebase Admin bootstrap and the admin-only auth dependency."""
import os

import firebase_admin
from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer
from firebase_admin import auth as fb_auth
from firebase_admin import credentials, firestore

_bearer = HTTPBearer(auto_error=False)


def _init_app() -> None:
    if firebase_admin._apps:
        return
    private_key = os.getenv("FIREBASE_PRIVATE_KEY", "").replace("\\n", "\n")
    cred = credentials.Certificate(
        {
            "type": "service_account",
            "project_id": os.getenv("FIREBASE_PROJECT_ID"),
            "client_email": os.getenv("FIREBASE_CLIENT_EMAIL"),
            "private_key": private_key,
            "token_uri": "https://oauth2.googleapis.com/token",
        }
    )
    firebase_admin.initialize_app(cred)


def get_db():
    _init_app()
    return firestore.client()


def require_admin(
    creds: HTTPAuthorizationCredentials | None = Depends(_bearer),
) -> dict:
    """Verifies the bearer token and confirms the caller is an active admin.

    Checks the Firestore profile rather than trusting the token's custom claim
    alone, so a demotion or ban takes effect without waiting for token refresh.
    """
    if creds is None:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Missing bearer token")

    try:
        decoded = fb_auth.verify_id_token(creds.credentials)
    except Exception as exc:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Invalid or expired token") from exc

    db = get_db()
    snap = db.collection("users").document(decoded["uid"]).get()
    if not snap.exists:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Account not found")

    profile = snap.to_dict()
    if profile.get("status") == "banned":
        raise HTTPException(status.HTTP_403_FORBIDDEN, "This account has been banned")
    if profile.get("role") != "admin":
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Admin access required")

    return {"uid": decoded["uid"], **profile}
