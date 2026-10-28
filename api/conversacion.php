<?php
/**
 * Módulo: Punto de entrada de la capa de aplicación
 * Responsabilidad: único punto de entrada. Admite exactamente tres operaciones (publicar
 *   comentario, publicar respuesta y obtener la conversación) y rechaza cualquier otra. Traduce
 *   cada resultado a una respuesta con el motivo exacto del requirements.md.
 * Decisiones: D-07, D-08, D-13
 * Requisitos: RN-009, RN-010, RN-011 · F-MOD-001 CA-4
 * Fronteras: lo llama la interfaz (js/conversacion.js). Usa los tres módulos de operación. No
 *   habla con el almacén directamente.
 * Estado: andamiaje — sin lógica todavía. El formato del contrato de red se define al empezar
 *   la lógica.
 */

require_once __DIR__ . '/publicacion_comentario.php';
require_once __DIR__ . '/publicacion_respuesta.php';
require_once __DIR__ . '/consulta_conversacion.php';
