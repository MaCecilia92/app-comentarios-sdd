<?php
/**
 * Módulo: Publicación de respuestas
 * Responsabilidad: valida la respuesta, comprueba que el comentario padre exista y la guarda
 *   ligada a él. Nunca se responde a una respuesta: el único padre posible es un comentario.
 * Decisiones: D-01, D-02, D-03, D-12, D-14
 * Requisitos: RN-001, RN-002, RN-003, RN-004, RN-005 · F-RES-001
 * Fronteras: usa validacion.php y acceso_datos.php. Lo invoca conversacion.php. No habla con
 *   la interfaz.
 */

require_once __DIR__ . '/validacion.php';
require_once __DIR__ . '/acceso_datos.php';

const MENSAJE_PADRE_INEXISTENTE = 'El comentario al que querés responder no existe'; // F-RES-001 · CA-5

/**
 * Devuelve ['codigo' => ..., 'exito' => ..., 'mensaje' => ...] según D-14.
 */
function publicar_respuesta($datos)
{
    $validacion = validar_publicacion($datos['autor'] ?? null, $datos['contenido'] ?? null, 'respuesta');
    if (!$validacion['valido']) {
        return ['codigo' => 400, 'exito' => false, 'mensaje' => $validacion['mensaje']];
    }

    $comentarioId = filter_var($datos['comentario_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($comentarioId === false || !existe_comentario($comentarioId)) {
        return ['codigo' => 404, 'exito' => false, 'mensaje' => MENSAJE_PADRE_INEXISTENTE];
    }

    try {
        guardar_respuesta($comentarioId, $validacion['autor'], $validacion['contenido']);
    } catch (PDOException $e) {
        // La integridad referencial del almacén (D-03) es la última garantía de RN-005.
        if ($e->getCode() === '23000') {
            return ['codigo' => 404, 'exito' => false, 'mensaje' => MENSAJE_PADRE_INEXISTENTE];
        }
        throw $e;
    }
    return ['codigo' => 200, 'exito' => true, 'mensaje' => 'Respuesta publicada correctamente']; // F-RES-001 · CA-2
}
