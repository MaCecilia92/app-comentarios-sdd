<?php
/**
 * Módulo: Punto de entrada de la capa de aplicación
 * Responsabilidad: único punto de entrada. Admite exactamente tres operaciones (publicar
 *   comentario, publicar respuesta y obtener la conversación) y rechaza cualquier otra. Traduce
 *   cada resultado a una respuesta con el motivo exacto del requirements.md.
 * Decisiones: D-07, D-08, D-13, D-14
 * Requisitos: RN-009, RN-010, RN-011 · F-MOD-001 CA-4
 * Fronteras: lo llama la interfaz (js/conversacion.js). Usa los tres módulos de operación. No
 *   habla con el almacén directamente.
 * Contrato (D-14):
 *   GET                                   -> 200 {exito, conversacion}
 *   POST {operacion: publicar_comentario} -> 200 / 400 {exito, mensaje}
 *   POST {operacion: publicar_respuesta}  -> 200 / 400 / 404 {exito, mensaje}
 *   DELETE                                -> 403 {exito: false, mensaje}
 *   cualquier otro método u operación     -> 405 {exito: false}
 *   El requirements.md no fija texto para el 405 ni para un error interno (500), así que esas
 *   respuestas no llevan mensaje; la interfaz muestra el de F-COM-002 · CA-6 (D-13).
 */

require_once __DIR__ . '/publicacion_comentario.php';
require_once __DIR__ . '/publicacion_respuesta.php';
require_once __DIR__ . '/consulta_conversacion.php';

function responder($codigo, $cuerpo)
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? '';

try {
    if ($metodo === 'GET') {
        responder(200, ['exito' => true, 'conversacion' => obtener_conversacion()]);
    }

    if ($metodo === 'POST') {
        $datos = json_decode(file_get_contents('php://input'), true);
        $operacion = is_array($datos) ? ($datos['operacion'] ?? null) : null;

        if ($operacion === 'publicar_comentario') {
            $resultado = publicar_comentario($datos);
        } elseif ($operacion === 'publicar_respuesta') {
            $resultado = publicar_respuesta($datos);
        } else {
            responder(405, ['exito' => false]);
        }
        responder($resultado['codigo'], ['exito' => $resultado['exito'], 'mensaje' => $resultado['mensaje']]);
    }

    if ($metodo === 'DELETE') {
        // D-08, D-10: no existe borrado en la v1.
        responder(403, ['exito' => false, 'mensaje' => 'No tenés permiso para borrar']); // F-MOD-001 · CA-4
    }

    responder(405, ['exito' => false]);
} catch (Throwable $e) {
    error_log('app-comentarios: ' . $e->getMessage());
    responder(500, ['exito' => false]);
}
