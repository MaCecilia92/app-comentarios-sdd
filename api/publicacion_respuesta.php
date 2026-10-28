<?php
/**
 * Módulo: Publicación de respuestas
 * Responsabilidad: valida la respuesta, comprueba que el comentario padre exista y la guarda
 *   ligada a él. Nunca se responde a una respuesta.
 * Decisiones: D-01, D-02, D-03, D-12
 * Requisitos: RN-001, RN-002, RN-003, RN-004, RN-005 · F-RES-001
 * Fronteras: usa validacion.php y acceso_datos.php. Lo invoca conversacion.php. No habla con
 *   la interfaz.
 * Estado: andamiaje — sin lógica todavía.
 */

require_once __DIR__ . '/validacion.php';
require_once __DIR__ . '/acceso_datos.php';
