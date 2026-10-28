<?php
/**
 * Módulo: Consulta de conversación
 * Responsabilidad: devuelve todos los comentarios con sus respuestas, ya ordenados:
 *   comentarios del más nuevo al más viejo, respuestas del más viejo al más nuevo, con el
 *   identificador como desempate. Solo lee.
 * Decisiones: D-04, D-12
 * Requisitos: RN-006 · F-COM-002 CA-1, CA-2 · F-RES-001 CA-2
 * Fronteras: usa acceso_datos.php. Lo invoca conversacion.php. No aplica reglas de escritura.
 * Estado: andamiaje — sin lógica todavía.
 */

require_once __DIR__ . '/acceso_datos.php';
