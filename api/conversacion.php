<?php
/**
 * Módulo: Punto de entrada de la capa de aplicación
 * Responsabilidad: único punto de entrada. Admite exactamente tres operaciones (publicar
 *   comentario, publicar respuesta y obtener la conversación) y rechaza cualquier otra. Traduce
 *   cada resultado a una respuesta con el motivo exacto del requirements.md.
 * Decisiones: D-07, D-08, D-10, D-13, D-14
 * Requisitos: RN-009, RN-010, RN-011 · F-MOD-001 CA-4
 * Fronteras: lo llama la interfaz (js/conversacion.js). Usa los tres módulos de operación. No
 *   habla con el almacén directamente.
 * Estado: implementado para F-RES-001 (+ mínimo de F-COM-001 y F-COM-002).
 */

require_once __DIR__ . '/publicacion_comentario.php';
require_once __DIR__ . '/publicacion_respuesta.php';
require_once __DIR__ . '/consulta_conversacion.php';

function responder($resultado)
{
    http_response_code($resultado['codigo']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resultado['cuerpo'], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Rechazo sin texto propio: la spec no define un mensaje para estos casos (D-07). */
function rechazar_operacion()
{
    responder(['codigo' => 405, 'cuerpo' => ['exito' => false]]);
}

try {
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($metodo === 'GET') {
        responder(obtener_conversacion());
    }

    if ($metodo === 'DELETE') {
        // D-08, D-10: no existe ninguna operación de borrado (F-MOD-001 CA-4).
        responder(['codigo' => 403, 'cuerpo' => ['exito' => false, 'mensaje' => 'No tenés permiso para borrar']]);
    }

    if ($metodo !== 'POST') {
        rechazar_operacion();
    }

    $pedido = json_decode(file_get_contents('php://input'), true);
    if (!is_array($pedido)) {
        rechazar_operacion();
    }

    $operacion = $pedido['operacion'] ?? null;
    if ($operacion === 'publicar_comentario') {
        responder(publicar_comentario($pedido['autor'] ?? null, $pedido['contenido'] ?? null));
    }
    if ($operacion === 'publicar_respuesta') {
        responder(publicar_respuesta(
            $pedido['comentario_id'] ?? null,
            $pedido['autor'] ?? null,
            $pedido['contenido'] ?? null
        ));
    }

    rechazar_operacion();
} catch (Throwable $error) {
    // Sin motivo que devolver: la interfaz muestra el mensaje de la spec según lo que hacía
    // (excepción de D-13).
    responder(['codigo' => 500, 'cuerpo' => ['exito' => false]]);
}
