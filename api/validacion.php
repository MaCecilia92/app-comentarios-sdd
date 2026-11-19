<?php
/**
 * Módulo: Validación
 * Responsabilidad: único punto de validación. Recorta los espacios de los extremos, mide en
 *   caracteres (no en bytes) y aplica las reglas de contenido (3 a 500) y de autor (2 a 100),
 *   devolviendo el motivo con el texto exacto del requirements.md.
 * Decisiones: D-01, D-02, D-07, D-13
 * Requisitos: RN-001, RN-002, RN-003, RN-010 · F-COM-001 CA-2 a CA-7 · F-RES-001 CA-3
 * Fronteras: lo usan publicacion_comentario.php y publicacion_respuesta.php. No habla con el
 *   almacén ni con la interfaz.
 */

// Textos exactos del requirements.md, por tipo de publicación (RN-010, D-07).
const MENSAJES_VALIDACION = [
    'comentario' => [
        'vacio' => 'Completá tu nombre y tu comentario',          // F-COM-001 · CA-5
        'corto' => 'El comentario debe tener al menos 3 caracteres', // F-COM-001 · CA-4
        'largo' => 'El comentario no puede superar los 500 caracteres', // F-COM-001 · CA-3
    ],
    'respuesta' => [
        'vacio' => 'Completá tu nombre y tu respuesta',           // F-RES-001 · CA-3
        'corto' => 'La respuesta debe tener al menos 3 caracteres',
        'largo' => 'La respuesta no puede superar los 500 caracteres',
    ],
];
const MENSAJE_AUTOR_INVALIDO = 'El nombre debe tener entre 2 y 100 caracteres'; // F-COM-001 · CA-6 · F-RES-001 · CA-3

/**
 * Recorta, mide en caracteres y valida autor y contenido (D-02).
 * $tipo es 'comentario' o 'respuesta' y solo elige el texto del mensaje: las reglas son las
 * mismas para los dos.
 * Devuelve ['valido' => true, 'autor' => ..., 'contenido' => ...] con el texto ya recortado,
 * o ['valido' => false, 'mensaje' => ...].
 */
function validar_publicacion($autor, $contenido, $tipo)
{
    $mensajes = MENSAJES_VALIDACION[$tipo];
    $autor = is_string($autor) ? trim($autor) : '';
    $contenido = is_string($contenido) ? trim($contenido) : '';

    if ($autor === '' || $contenido === '') {
        return ['valido' => false, 'mensaje' => $mensajes['vacio']];
    }

    $largoContenido = mb_strlen($contenido, 'UTF-8');
    if ($largoContenido < 3) {
        return ['valido' => false, 'mensaje' => $mensajes['corto']];
    }
    if ($largoContenido > 500) {
        return ['valido' => false, 'mensaje' => $mensajes['largo']];
    }

    $largoAutor = mb_strlen($autor, 'UTF-8');
    if ($largoAutor < 2 || $largoAutor > 100) {
        return ['valido' => false, 'mensaje' => MENSAJE_AUTOR_INVALIDO];
    }

    return ['valido' => true, 'autor' => $autor, 'contenido' => $contenido];
}
