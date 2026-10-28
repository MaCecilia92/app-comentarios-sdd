<?php
/**
 * Módulo: Acceso a datos
 * Responsabilidad: frontera única con el almacén. Lee y guarda comentarios y respuestas con
 *   consultas preparadas, sin conocer reglas de negocio. Cuando se implemente, abrirá la
 *   conexión con la zona horaria UTC (D-12); en el andamiaje no abre ninguna conexión.
 * Decisiones: D-05, D-12
 * Requisitos: RN-003 · RN-008
 * Fronteras: lo usan publicacion_comentario.php, publicacion_respuesta.php y
 *   consulta_conversacion.php. Lee la configuración de config.php. No habla con la interfaz.
 * Estado: andamiaje — sin lógica todavía.
 */

require_once __DIR__ . '/../config.php';
