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
 * Estado: andamiaje — sin lógica todavía.
 */
