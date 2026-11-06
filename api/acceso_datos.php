<?php
/**
 * Módulo: Acceso a datos
 * Responsabilidad: frontera única con el almacén. Lee y guarda comentarios y respuestas con
 *   consultas preparadas, sin conocer reglas de negocio. La conexión trabaja en UTC (D-12).
 * Decisiones: D-03, D-04, D-05, D-12
 * Requisitos: RN-003 · RN-005 · RN-006 · RN-008
 * Fronteras: lo usan publicacion_comentario.php, publicacion_respuesta.php y
 *   consulta_conversacion.php. Lee la configuración de config.php. No habla con la interfaz.
 * Estado: implementado para F-RES-001 (+ mínimo de F-COM-001 y F-COM-002).
 */

require_once __DIR__ . '/../config.php';

function conexion()
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}

function guardar_comentario($autor, $contenido)
{
    $consulta = conexion()->prepare('INSERT INTO comentario (autor, contenido) VALUES (?, ?)');
    $consulta->execute([$autor, $contenido]);
}

function existe_comentario($id)
{
    $consulta = conexion()->prepare('SELECT 1 FROM comentario WHERE id = ?');
    $consulta->execute([$id]);
    return $consulta->fetchColumn() !== false;
}

function guardar_respuesta($comentarioId, $autor, $contenido)
{
    $consulta = conexion()->prepare(
        'INSERT INTO respuesta (comentario_id, autor, contenido) VALUES (?, ?, ?)'
    );
    $consulta->execute([$comentarioId, $autor, $contenido]);
}

/** Comentarios del más nuevo al más viejo, con el identificador como desempate (D-04). */
function leer_comentarios()
{
    return conexion()->query(
        'SELECT id, autor, contenido, fecha_creacion FROM comentario
         ORDER BY fecha_creacion DESC, id DESC'
    )->fetchAll();
}

/** Respuestas de cada comentario de la más vieja a la más nueva (D-04). */
function leer_respuestas()
{
    return conexion()->query(
        'SELECT id, comentario_id, autor, contenido, fecha_creacion FROM respuesta
         ORDER BY comentario_id, fecha_creacion ASC, id ASC'
    )->fetchAll();
}
