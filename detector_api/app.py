from functools import wraps

from flask import Flask, Response, jsonify, request

from camara import camara
from config import API_SECRET

app = Flask(__name__)
ORIGENES_PERMITIDOS = {"http://localhost", "http://127.0.0.1"}


def authorized(fn):
    @wraps(fn)
    def wrapper(*args, **kwargs):
        if request.headers.get("X-API-KEY") != API_SECRET:
            return jsonify({"ok": False, "message": "No autorizado"}), 401
        return fn(*args, **kwargs)
    return wrapper


@app.get("/status")
def status():
    estado = camara.obtener_estado()
    return jsonify({"running": bool(estado.get("activa")), "error": estado.get("error")})


@app.post("/start")
@authorized
def start():
    data = request.get_json(silent=True) or {}
    try:
        user_id = int(data.get("user_id", 0))
        if user_id <= 0:
            raise ValueError
    except (TypeError, ValueError):
        return jsonify({"ok": False, "message": "Identificador de usuario inválido."}), 400

    iniciado, mensaje = camara.iniciar(user_id)
    if not iniciado:
        return jsonify({"ok": False, "message": mensaje}), 409
    return jsonify({"ok": True, "message": mensaje, "user_id": user_id}), 200


@app.post("/stop")
@authorized
def stop():
    detenido, mensaje = camara.detener_monitor()
    if not detenido:
        return jsonify({"ok": False, "message": mensaje}), 409
    return jsonify({"ok": True, "message": mensaje}), 200


@app.get("/video_feed")
def video_feed():
    return Response(
        camara.transmitir(),
        mimetype="multipart/x-mixed-replace; boundary=frame",
        headers={"Cache-Control": "no-store, no-cache, must-revalidate"},
    )


@app.get("/estado")
def estado():
    respuesta = jsonify(camara.obtener_estado())
    origen = request.headers.get("Origin")
    if origen in ORIGENES_PERMITIDOS:
        respuesta.headers["Access-Control-Allow-Origin"] = origen
        respuesta.headers["Vary"] = "Origin"
    return respuesta


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=False, threaded=True)
