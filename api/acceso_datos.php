<?php
/**
 * Módulo: Acceso a datos
 * Responsabilidad: frontera única con el almacén. Lee y guarda comentarios y respuestas con
 *   consultas preparadas, sin conocer reglas de negocio. Abre la conexión con la zona horaria
 *   UTC (D-12).
 * Decisiones: D-05, D-12
 * Requisitos: RN-003 · RN-008
 * Fronteras: lo usan publicacion_comentario.php, publicacion_respuesta.php y
 *   consulta_conversacion.php. Lee la configuración de config.php. No habla con la interfaz.
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
        $pdo->exec("SET time_zone = '+00:00'"); // D-12
    }
    return $pdo;
}

function guardar_comentario($autor, $contenido)
{
    $sentencia = conexion()->prepare(
        'INSERT INTO comentario (autor, contenido, fecha_creacion) VALUES (?, ?, UTC_TIMESTAMP())'
    );
    $sentencia->execute([$autor, $contenido]);
}

function guardar_respuesta($comentarioId, $autor, $contenido)
{
    $sentencia = conexion()->prepare(
        'INSERT INTO respuesta (comentario_id, autor, contenido, fecha_creacion) VALUES (?, ?, ?, UTC_TIMESTAMP())'
    );
    $sentencia->execute([$comentarioId, $autor, $contenido]);
}

function existe_comentario($comentarioId)
{
    $sentencia = conexion()->prepare('SELECT 1 FROM comentario WHERE id = ?');
    $sentencia->execute([$comentarioId]);
    return $sentencia->fetchColumn() !== false;
}

// Orden de D-04: fecha y hora, con el identificador como desempate.
function leer_comentarios()
{
    return conexion()->query(
        'SELECT id, autor, contenido, fecha_creacion FROM comentario ORDER BY fecha_creacion DESC, id DESC'
    )->fetchAll();
}

function leer_respuestas()
{
    return conexion()->query(
        'SELECT id, comentario_id, autor, contenido, fecha_creacion FROM respuesta ORDER BY comentario_id, fecha_creacion ASC, id ASC'
    )->fetchAll();
}
