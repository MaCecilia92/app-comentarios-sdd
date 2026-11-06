<?php
/**
 * Módulo: Publicación de comentarios
 * Responsabilidad: valida y guarda un comentario nuevo con su fecha y hora de publicación.
 * Decisiones: D-01, D-02, D-05, D-12, D-14
 * Requisitos: RN-001, RN-002, RN-003 · F-COM-001
 * Fronteras: usa validacion.php y acceso_datos.php. Lo invoca conversacion.php. No habla con
 *   la interfaz.
 * Estado: implementado (mínimo de F-COM-001 para F-RES-001).
 */

require_once __DIR__ . '/validacion.php';
require_once __DIR__ . '/acceso_datos.php';

/** Devuelve ['codigo' => int, 'cuerpo' => array] según el contrato de D-14. */
function publicar_comentario($autor, $contenido)
{
    $validacion = validar_publicacion($autor, $contenido, 'comentario');
    if (!$validacion['ok']) {
        return ['codigo' => 400, 'cuerpo' => ['exito' => false, 'mensaje' => $validacion['mensaje']]];
    }

    guardar_comentario($validacion['autor'], $validacion['contenido']);

    return ['codigo' => 200, 'cuerpo' => ['exito' => true, 'mensaje' => 'Comentario publicado correctamente']];
}
