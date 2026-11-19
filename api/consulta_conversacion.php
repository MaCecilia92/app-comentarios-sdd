<?php
/**
 * Módulo: Consulta de conversación
 * Responsabilidad: devuelve todos los comentarios con sus respuestas, ya ordenados:
 *   comentarios del más nuevo al más viejo, respuestas del más viejo al más nuevo, con el
 *   identificador como desempate. Solo lee.
 * Decisiones: D-04, D-12, D-14
 * Requisitos: RN-006 · F-COM-002 CA-1, CA-2 · F-RES-001 CA-2
 * Fronteras: usa acceso_datos.php. Lo invoca conversacion.php. No aplica reglas de escritura.
 */

require_once __DIR__ . '/acceso_datos.php';

// La fecha se entrega en ISO 8601 con zona UTC explícita, para que la interfaz la convierta
// a la hora local del lector (D-12).
function fecha_utc($fecha)
{
    return str_replace(' ', 'T', $fecha) . 'Z';
}

function obtener_conversacion()
{
    $respuestasPorComentario = [];
    foreach (leer_respuestas() as $fila) {
        $respuestasPorComentario[$fila['comentario_id']][] = [
            'id' => (int) $fila['id'],
            'autor' => $fila['autor'],
            'contenido' => $fila['contenido'],
            'fecha_creacion' => fecha_utc($fila['fecha_creacion']),
        ];
    }

    $conversacion = [];
    foreach (leer_comentarios() as $fila) {
        $conversacion[] = [
            'id' => (int) $fila['id'],
            'autor' => $fila['autor'],
            'contenido' => $fila['contenido'],
            'fecha_creacion' => fecha_utc($fila['fecha_creacion']),
            'respuestas' => $respuestasPorComentario[$fila['id']] ?? [],
        ];
    }
    return $conversacion;
}
