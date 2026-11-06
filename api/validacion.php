<?php
/**
 * Módulo: Validación
 * Responsabilidad: único punto de validación. Recorta los espacios de los extremos, mide en
 *   caracteres (no en bytes) y aplica las reglas de contenido (3 a 500) y de autor (2 a 100),
 *   devolviendo un solo motivo con el texto exacto del requirements.md.
 * Decisiones: D-01, D-02, D-07, D-13, D-15
 * Requisitos: RN-001, RN-002, RN-003, RN-010 · F-COM-001 CA-2 a CA-7 · F-RES-001 CA-3
 * Fronteras: lo usan publicacion_comentario.php y publicacion_respuesta.php. No habla con el
 *   almacén ni con la interfaz.
 * Estado: implementado para F-RES-001 (+ mínimo de F-COM-001).
 */

const MENSAJES_VALIDACION = [
    'comentario' => [
        'vacio' => 'Completá tu nombre y tu comentario',
        'corto' => 'El comentario debe tener al menos 3 caracteres',
        'largo' => 'El comentario no puede superar los 500 caracteres',
    ],
    'respuesta' => [
        'vacio' => 'Completá tu nombre y tu respuesta',
        'corto' => 'La respuesta debe tener al menos 3 caracteres',
        'largo' => 'La respuesta no puede superar los 500 caracteres',
    ],
];

const MENSAJE_AUTOR = 'El nombre debe tener entre 2 y 100 caracteres';

/**
 * Valida autor y contenido de una publicación ($tipo: 'comentario' o 'respuesta').
 * Orden de los rechazos según D-15: vacío → longitud del contenido → longitud del autor.
 */
function validar_publicacion($autor, $contenido, $tipo)
{
    $mensajes = MENSAJES_VALIDACION[$tipo];
    $autor = is_string($autor) ? trim($autor) : '';
    $contenido = is_string($contenido) ? trim($contenido) : '';

    $rechazo = function ($mensaje) {
        return ['ok' => false, 'mensaje' => $mensaje];
    };

    if ($autor === '' || $contenido === '') {
        return $rechazo($mensajes['vacio']);
    }

    $largoContenido = mb_strlen($contenido, 'UTF-8');
    if ($largoContenido < 3) {
        return $rechazo($mensajes['corto']);
    }
    if ($largoContenido > 500) {
        return $rechazo($mensajes['largo']);
    }

    $largoAutor = mb_strlen($autor, 'UTF-8');
    if ($largoAutor < 2 || $largoAutor > 100) {
        return $rechazo(MENSAJE_AUTOR);
    }

    return ['ok' => true, 'autor' => $autor, 'contenido' => $contenido];
}
