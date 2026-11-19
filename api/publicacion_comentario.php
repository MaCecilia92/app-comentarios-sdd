<?php
/**
 * Módulo: Publicación de comentarios
 * Responsabilidad: valida y guarda un comentario nuevo con su fecha y hora de publicación.
 * Decisiones: D-01, D-02, D-12, D-14
 * Requisitos: RN-001, RN-002, RN-003 · F-COM-001
 * Fronteras: usa validacion.php y acceso_datos.php. Lo invoca conversacion.php. No habla con
 *   la interfaz.
 */

require_once __DIR__ . '/validacion.php';
require_once __DIR__ . '/acceso_datos.php';

/**
 * Devuelve ['codigo' => ..., 'exito' => ..., 'mensaje' => ...] según D-14.
 */
function publicar_comentario($datos)
{
    $validacion = validar_publicacion($datos['autor'] ?? null, $datos['contenido'] ?? null, 'comentario');
    if (!$validacion['valido']) {
        return ['codigo' => 400, 'exito' => false, 'mensaje' => $validacion['mensaje']];
    }

    guardar_comentario($validacion['autor'], $validacion['contenido']);
    return ['codigo' => 200, 'exito' => true, 'mensaje' => 'Comentario publicado correctamente']; // F-COM-001 · CA-1
}
