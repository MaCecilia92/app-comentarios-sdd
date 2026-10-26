# AGENTS.md — api/ (capa de aplicación)

Reglas que solo valen dentro de `api/`. Se suman a las del [`AGENTS.md`](../AGENTS.md) de la
raíz.

1. Accedé a la base solo con PDO y consultas preparadas; nunca armes una consulta
   concatenando texto que venga del lector. (origen: D-05 · design.md §3, "Acceso a datos")
2. Ofrecé exactamente tres operaciones: publicar comentario, publicar respuesta y obtener la
   conversación. Cualquier otro método o pedido se rechaza. (origen: D-08 · RN-009, RN-011)
3. Validá con las mismas reglas para comentarios y respuestas, reutilizando un único punto de
   validación. (origen: D-01, D-02 · design.md §3, "Validación")
4. Ante un rechazo, respondé con el motivo usando el texto exacto del `requirements.md`.
   (origen: D-07 · RN-010)
5. Guardá el texto recortado y sin otra transformación: no lo escapes ni lo limpies al
   guardarlo. (origen: D-05 · RN-008)
6. Leé las credenciales solo de `config.php`; no las repitas en otros archivos. (origen:
   design.md §3, "Configuración")
7. No ejecutes cambios de estructura de la base (crear o alterar tablas) desde la API.
   (origen: design.md §4)
