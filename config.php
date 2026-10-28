<?php
/**
 * Módulo: Configuración
 * Responsabilidad: ubicación y credenciales del almacén, en un único lugar separado de la
 *   lógica.
 * Decisiones: D-09
 * Requisitos: design.md §3 "Configuración"
 * Fronteras: lo lee solo api/acceso_datos.php.
 * Estado: valores del entorno local (XAMPP).
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'app_comentarios');
define('DB_USER', 'root');
define('DB_PASS', '');
