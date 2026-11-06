<?php
/**
 * Módulo: Publicación de respuestas
 * Responsabilidad: valida la respuesta, comprueba que el comentario padre exista y la guarda
 *   ligada a él. Nunca se responde a una respuesta: el identificador solo apunta a comentarios.
 * Decisiones: D-01, D-02, D-03, D-05, D-12, D-14, D-15
 * Requisitos: RN-001, RN-002, RN-003, RN-004, RN-005 · F-RES-001
 * Fronteras: usa validacion.php y acceso_datos.php. Lo invoca conversacion.php. No habla con
 *   la interfaz.
 * Estado: implementado para F-RES-001.
 */

require_once __DIR__ . '/validacion.php';
require_once __DIR__ . '/acceso_datos.php';

const MENSAJE_PADRE_INEXISTENTE = 'El comentario al que querés responder no existe';

/** Devuelve ['codigo' => int, 'cuerpo' => array] según el contrato de D-14. */
function publicar_respuesta($comentarioId, $autor, $contenido)
{
    // D-15: primero los datos (400), después el comentario padre (404).
    $validacion = validar_publicacion($autor, $contenido, 'respuesta');
    if (!$validacion['ok']) {
        return ['codigo' => 400, 'cuerpo' => ['exito' => false, 'mensaje' => $validacion['mensaje']]];
    }

    // D-15: un identificador ausente o que no es un entero positivo es un padre inexistente.
    $id = filter_var($comentarioId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || !existe_comentario($id)) {
        return ['codigo' => 404, 'cuerpo' => ['exito' => false, 'mensaje' => MENSAJE_PADRE_INEXISTENTE]];
    }

    guardar_respuesta($id, $validacion['autor'], $validacion['contenido']);

    return ['codigo' => 200, 'cuerpo' => ['exito' => true, 'mensaje' => 'Respuesta publicada correctamente']];
}
