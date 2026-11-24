"""Motor de cámara: administra el monitoreo y registra sesiones/alertas."""
import threading
import time
from datetime import datetime

import cv2
import mediapipe as mp
import mysql.connector

from config import (
    DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD,
    CAMERA_INDEX, EAR_THRESHOLD, DROWSY_SECONDS,
    ALARM_FREQUENCY, ALARM_DURATION_MS,
)
from detector import calcular_ear, INDICES_OJO_IZQ, INDICES_OJO_DER, winsound

ESTADO_INICIAL = {
    "activa": False,
    "rostro": False,
    "ear": None,
    "alerta": False,
    "segundos_cerrados": 0.0,
    "error": None,
    "aviso": None,
}


class Camara:
    def __init__(self):
        self.lock = threading.RLock()
        self.frame = None
        self.estado = dict(ESTADO_INICIAL)
        self.hilo = None
        self.detener = threading.Event()
        self.id_sesion = None
        self.user_id = None

    def _conectar_db(self):
        return mysql.connector.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USER,
            password=DB_PASSWORD,
            database=DB_NAME,
            connection_timeout=5,
        )

    def iniciar(self, user_id):
        """Arranca el hilo de cámara; la sesión se crea dentro de ese hilo."""
        user_id = int(user_id)
        with self.lock:
            if self.hilo is not None and self.hilo.is_alive():
                if self.user_id == user_id:
                    return False, "Ya existe un monitoreo activo para tu usuario."
                return False, "El detector ya está siendo utilizado por otro usuario."

            self.detener.clear()
            self.frame = None
            self.user_id = user_id
            self.id_sesion = None
            self.estado = dict(ESTADO_INICIAL)
            self.estado["aviso"] = "Iniciando cámara y preparando el registro..."
            self.hilo = threading.Thread(
                target=self._bucle,
                args=(user_id,),
                name=f"vigia-camera-{user_id}",
                daemon=True,
            )
            self.hilo.start()
        return True, "Solicitud aceptada. Se está iniciando el monitoreo."

    def detener_monitor(self):
        with self.lock:
            hilo = self.hilo
            if hilo is None or not hilo.is_alive():
                return False, "No hay un monitoreo activo."
            self.detener.set()

        # Espera fuera del lock para permitir que el hilo termine y actualice estado.
        hilo.join(timeout=8)
        if hilo.is_alive():
            return True, "Se solicitó detener el monitoreo; está finalizando."
        return True, "Monitoreo detenido y sesión cerrada."

    def obtener_estado(self):
        with self.lock:
            return dict(self.estado)

    def transmitir(self):
        """Entrega los fotogramas; abrir el video NO inicia ni detiene el detector."""
        ultimo_frame = None
        while True:
            with self.lock:
                frame = self.frame
                hilo_vivo = self.hilo is not None and self.hilo.is_alive()

            if frame is not None and frame is not ultimo_frame:
                ultimo_frame = frame
                yield b"--frame\r\nContent-Type: image/jpeg\r\n\r\n" + frame + b"\r\n"
            elif not hilo_vivo:
                # No mantiene solicitudes HTTP abiertas cuando el detector está parado.
                return
            else:
                time.sleep(0.02)

    def _actualizar_estado(self, **cambios):
        with self.lock:
            self.estado.update(cambios)

    def _bucle(self, user_id):
        cap = None
        face_mesh = None
        db = None
        cursor = None
        id_sesion = None
        cantidad_alertas = 0
        inicio_monotonic = time.monotonic()
        error_final = None
        inicio_cierre = None
        alerta_guardada = False
        id_alerta_abierta = None
        ultimo_pitido = 0.0

        def cerrar_alerta_abierta():
            """Guarda cuánto duró en total el cierre de ojos de la última alerta."""
            nonlocal id_alerta_abierta
            if id_alerta_abierta is None or inicio_cierre is None:
                id_alerta_abierta = None
                return
            try:
                cursor.execute(
                    "UPDATE alertas SET duracion_cierre_segundos = %s WHERE id_alerta = %s",
                    (round(time.monotonic() - inicio_cierre, 2), id_alerta_abierta),
                )
                db.commit()
            except Exception as exc:
                print(f"[BD ERROR] No se pudo actualizar la duración de la alerta: {exc}")
            id_alerta_abierta = None

        try:
            # La cámara se abre primero. No se crea una sesión si no hay cámara.
            cap = cv2.VideoCapture(CAMERA_INDEX, cv2.CAP_DSHOW)
            if not cap.isOpened():
                raise RuntimeError("No se pudo abrir la cámara. Verifica permisos y CAMERA_INDEX.")

            # No se permite ejecutar una sesión sin conexión a la base de datos.
            try:
                db = self._conectar_db()
                cursor = db.cursor()
                cursor.execute(
                    "INSERT INTO sesiones (id_usuario, id_vehiculo, fecha_inicio, estado) "
                    "VALUES (%s, (SELECT id_vehiculo FROM vehiculos "
                    "WHERE id_cliente_seleccion = %s LIMIT 1), %s, 'EN_CURSO')",
                    (user_id, user_id, datetime.now()),
                )
                id_sesion = cursor.lastrowid
                db.commit()
            except Exception as exc:
                if db:
                    try:
                        db.rollback()
                    except Exception:
                        pass
                raise RuntimeError(
                    "No se pudo crear la sesión en MariaDB. Revisa DB_NAME, credenciales y tablas. "
                    f"Detalle: {exc}"
                ) from exc

            with self.lock:
                self.id_sesion = id_sesion

            face_mesh = mp.solutions.face_mesh.FaceMesh(
                max_num_faces=1,
                refine_landmarks=True,
                min_detection_confidence=0.5,
                min_tracking_confidence=0.5,
            )

            self._actualizar_estado(
                activa=True,
                error=None,
                aviso=f"Sesión #{id_sesion} registrada correctamente.",
            )
            print(f"[BD OK] Sesión #{id_sesion} iniciada para usuario {user_id}")

            while not self.detener.is_set():
                ret, frame = cap.read()
                if not ret:
                    raise RuntimeError("La cámara dejó de entregar imágenes.")

                frame = cv2.flip(frame, 1)
                height, width, _ = frame.shape
                results = face_mesh.process(cv2.cvtColor(frame, cv2.COLOR_BGR2RGB))

                ear = None
                alerta = False
                cerrados = 0.0

                if results.multi_face_landmarks:
                    face_landmarks = results.multi_face_landmarks[0]
                    landmarks = face_landmarks.landmark
                    ear_izq = calcular_ear(landmarks, INDICES_OJO_IZQ, width, height)
                    ear_der = calcular_ear(landmarks, INDICES_OJO_DER, width, height)
                    ear = (ear_izq + ear_der) / 2.0

                    if ear < EAR_THRESHOLD:
                        if inicio_cierre is None:
                            inicio_cierre = time.monotonic()
                        cerrados = time.monotonic() - inicio_cierre
                        alerta = cerrados >= DROWSY_SECONDS

                        # Un registro por episodio de cierre prolongado. El pitido puede
                        # repetirse, pero no se insertan filas duplicadas por cada pitido.
                        if alerta and not alerta_guardada:
                            try:
                                cursor.execute(
                                    "INSERT INTO alertas "
                                    "(id_sesion, fecha_hora, ear, duracion_cierre_segundos) "
                                    "VALUES (%s, %s, %s, %s)",
                                    (id_sesion, datetime.now(), round(ear, 4), round(cerrados, 2)),
                                )
                                id_alerta_abierta = cursor.lastrowid
                                cursor.execute(
                                    "UPDATE sesiones SET cantidad_alertas = cantidad_alertas + 1 "
                                    "WHERE id_sesion = %s",
                                    (id_sesion,),
                                )
                                db.commit()
                                cantidad_alertas += 1
                                alerta_guardada = True
                                self._actualizar_estado(aviso=None)
                                print(
                                    f"[BD OK] Alerta #{cantidad_alertas} guardada en sesión "
                                    f"#{id_sesion} (EAR: {ear:.4f})"
                                )
                            except Exception as exc:
                                db.rollback()
                                self._actualizar_estado(
                                    aviso=f"No se pudo guardar una alerta en MariaDB: {exc}"
                                )
                                print(f"[BD ERROR] Error al guardar alerta: {exc}")
                    else:
                        cerrar_alerta_abierta()
                        inicio_cierre = None
                        alerta_guardada = False

                    mp.solutions.drawing_utils.draw_landmarks(
                        image=frame,
                        landmark_list=face_landmarks,
                        connections=mp.solutions.face_mesh.FACEMESH_TESSELATION,
                        landmark_drawing_spec=None,
                        connection_drawing_spec=(
                            mp.solutions.drawing_styles.get_default_face_mesh_tesselation_style()
                        ),
                    )

                    if alerta:
                        cv2.rectangle(frame, (0, 0), (width, height), (0, 0, 255), 10)
                        cv2.putText(
                            frame, "ALERTA: SOMNOLENCIA DETECTADA", (30, 70),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 0, 255), 3,
                        )
                    else:
                        cv2.rectangle(frame, (0, 0), (260, 55), (0, 0, 0), -1)
                        cv2.putText(
                            frame, f"EAR: {ear:.2f}", (10, 36),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2,
                        )
                else:
                    cerrar_alerta_abierta()
                    inicio_cierre = None
                    alerta_guardada = False

                if alerta and winsound and time.monotonic() - ultimo_pitido > 0.5:
                    ultimo_pitido = time.monotonic()
                    threading.Thread(
                        target=winsound.Beep,
                        args=(ALARM_FREQUENCY, ALARM_DURATION_MS),
                        daemon=True,
                    ).start()

                ok, jpg = cv2.imencode(
                    ".jpg", frame, [cv2.IMWRITE_JPEG_QUALITY, 80]
                )
                if ok:
                    with self.lock:
                        self.frame = jpg.tobytes()
                        self.estado.update({
                            "activa": True,
                            "rostro": ear is not None,
                            "ear": None if ear is None else round(ear, 3),
                            "alerta": alerta,
                            "segundos_cerrados": round(cerrados, 1),
                        })

        except Exception as exc:
            error_final = str(exc)
            print(f"[DETECTOR ERROR] {error_final}")
            self._actualizar_estado(activa=False, error=error_final)

        finally:
            duracion = int(time.monotonic() - inicio_monotonic)
            if id_sesion is not None and cursor is not None and db is not None:
                cerrar_alerta_abierta()
                try:
                    estado_final = 'ERROR' if error_final else 'FINALIZADA'
                    cursor.execute(
                        "UPDATE sesiones SET fecha_fin = %s, duracion_segundos = %s, "
                        "cantidad_alertas = %s, estado = %s WHERE id_sesion = %s",
                        (datetime.now(), duracion, cantidad_alertas, estado_final, id_sesion),
                    )
                    db.commit()
                    print(
                        f"[BD OK] Sesión #{id_sesion} cerrada como {estado_final}. "
                        f"Alertas: {cantidad_alertas}"
                    )
                except Exception as exc:
                    print(f"[BD ERROR] No se pudo cerrar la sesión #{id_sesion}: {exc}")
                    if not error_final:
                        error_final = f"No se pudo finalizar la sesión en MariaDB: {exc}"

            if face_mesh is not None:
                face_mesh.close()
            if cap is not None:
                cap.release()
            if cursor is not None:
                cursor.close()
            if db is not None:
                db.close()

            with self.lock:
                self.frame = None
                self.user_id = None
                self.id_sesion = None
                self.estado.update({
                    "activa": False,
                    "rostro": False,
                    "ear": None,
                    "alerta": False,
                    "segundos_cerrados": 0.0,
                    "error": error_final,
                    "aviso": None if error_final else "Monitoreo finalizado.",
                })


camara = Camara()
