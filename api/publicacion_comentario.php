<?php
/**
 * Módulo: Publicación de comentarios
 * Responsabilidad: valida y guarda un comentario nuevo con su fecha y hora de publicación.
 * Decisiones: D-01, D-02, D-12
 * Requisitos: RN-001, RN-002, RN-003 · F-COM-001
 * Fronteras: usa validacion.php y acceso_datos.php. Lo invoca conversacion.php. No habla con
 *   la interfaz.
 * Estado: andamiaje — sin lógica todavía.
 */

require_once __DIR__ . '/validacion.php';
require_once __DIR__ . '/acceso_datos.php';
