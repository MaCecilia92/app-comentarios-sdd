<?php
/**
 * Módulo: Consulta de conversación
 * Responsabilidad: devuelve todos los comentarios con sus respuestas, ya ordenados:
 *   comentarios del más nuevo al más viejo, respuestas del más viejo al más nuevo, con el
 *   identificador como desempate. Solo lee.
 * Decisiones: D-04, D-12, D-14, D-15
 * Requisitos: RN-006 · F-COM-002 CA-1, CA-2 · F-RES-001 CA-2
 * Fronteras: usa acceso_datos.php. Lo invoca conversacion.php. No aplica reglas de escritura.
 * Estado: implementado (mínimo de F-COM-002 para F-RES-001).
 */

require_once __DIR__ . '/acceso_datos.php';

/** Formato del contrato (D-15): identificador entero y fecha ISO 8601 en UTC. */
function formatear_publicacion($fila)
{
    return [
        'id' => (int) $fila['id'],
        'autor' => $fila['autor'],
        'contenido' => $fila['contenido'],
        'fecha_creacion' => str_replace(' ', 'T', $fila['fecha_creacion']) . 'Z',
    ];
}

/** Devuelve ['codigo' => int, 'cuerpo' => array] según el contrato de D-14. */
function obtener_conversacion()
{
    $conversacion = [];
    $posicion = [];
    foreach (leer_comentarios() as $fila) {
        $comentario = formatear_publicacion($fila);
        $comentario['respuestas'] = [];
        $posicion[$comentario['id']] = count($conversacion);
        $conversacion[] = $comentario;
    }

    // Las respuestas ya llegan ordenadas: solo se anidan, sin reordenar (D-04).
    foreach (leer_respuestas() as $fila) {
        $padre = (int) $fila['comentario_id'];
        if (isset($posicion[$padre])) {
            $conversacion[$posicion[$padre]]['respuestas'][] = formatear_publicacion($fila);
        }
    }

    return ['codigo' => 200, 'cuerpo' => ['exito' => true, 'conversacion' => $conversacion]];
}
