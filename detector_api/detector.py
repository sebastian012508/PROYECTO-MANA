"""Funciones de cálculo del EAR compartidas por el módulo de cámara."""
import math

try:
    import winsound
except ImportError:  # winsound solo está disponible en Windows.
    winsound = None

INDICES_OJO_IZQ = [33, 160, 158, 133, 153, 144]
INDICES_OJO_DER = [362, 385, 387, 263, 373, 380]


def calcular_distancia(p1, p2):
    return math.hypot(p2[0] - p1[0], p2[1] - p1[1])


def calcular_ear(landmarks, indices, width, height):
    """Calcula Eye Aspect Ratio a partir de 6 puntos del ojo."""
    puntos = [[landmarks[i].x * width, landmarks[i].y * height] for i in indices]
    vertical_1 = calcular_distancia(puntos[1], puntos[5])
    vertical_2 = calcular_distancia(puntos[2], puntos[4])
    horizontal = calcular_distancia(puntos[0], puntos[3])
    return 0.0 if horizontal == 0 else (vertical_1 + vertical_2) / (2.0 * horizontal)
