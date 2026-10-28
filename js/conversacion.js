/*
 * Módulo: Interfaz de conversación
 * Responsabilidad: muestra la lista con su contador, el formulario de nuevo comentario y el
 *   formulario de respuesta de cada comentario. No valida antes de enviar (D-13): envía,
 *   muestra el motivo que devuelve el servidor junto al formulario que lo originó (D-07),
 *   inserta todo texto del lector como texto literal (D-05), muestra la fecha en la hora local
 *   del lector (D-12), no reordena (D-04) y después de publicar vuelve a pedir la conversación
 *   completa (D-06).
 * Decisiones: D-04, D-05, D-06, D-07, D-12, D-13
 * Requisitos: RN-006, RN-008, RN-010 · F-COM-001 · F-COM-002 · F-RES-001
 * Fronteras: habla solo con api/conversacion.php. No accede a datos ni decide reglas.
 * Estado: andamiaje — sin lógica todavía.
 */
