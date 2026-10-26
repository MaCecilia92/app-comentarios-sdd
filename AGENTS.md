# AGENTS.md — App de Comentarios

Reglas del proyecto para cualquier agente de código (OpenCode, Claude Code, Cursor). Se
destilan de la spec; cada regla cita su origen. Si una regla no se puede trazar a un
requisito o a una decisión, no va acá.

## Propósito y fuentes de verdad

App web de una sola conversación donde los lectores publican comentarios y respuestas de un
nivel. Antes de proponer o cambiar algo, leé:

- [`specs/001-publicar-comentarios/requirements.md`](specs/001-publicar-comentarios/requirements.md) — el QUÉ (features, criterios CA, reglas RN, alcance).
- [`specs/001-publicar-comentarios/design.md`](specs/001-publicar-comentarios/design.md) — el CÓMO (decisiones D-xx, módulos).

Si el código y la spec no coinciden, la spec manda. Si la spec no responde algo, preguntá
antes de decidir; no completes con suposiciones.

## Reglas

1. Validá toda regla de negocio en el servidor antes de cualquier escritura; la validación del navegador es solo un aviso anticipado, nunca la barrera. (origen: D-01 · RN-001, RN-002, RN-003)
2. Recortá los espacios al principio y al final del autor y del contenido antes de validar, y medí la longitud en caracteres, no en bytes: contenido 3 a 500, autor 2 a 100. (origen: D-02 · RN-001, RN-003)
3. Modelá comentario y respuesta como dos entidades: una respuesta siempre referencia un comentario existente y nunca otra respuesta. (origen: D-03 · RN-004, RN-005)
4. Entregá la conversación ya ordenada desde el servidor: comentarios del más nuevo al más viejo, respuestas del más viejo al más nuevo, con el identificador como desempate. La interfaz no reordena. (origen: D-04 · RN-006)
5. Mostrá todo texto ingresado por un lector como texto literal, nunca como HTML, conservando los saltos de línea. (origen: D-05 · RN-008)
6. Después de cada publicación exitosa, volvé a pedir la conversación completa; la interfaz no mantiene una copia propia. (origen: D-06 · F-COM-002 CA-5)
7. Usá los mensajes de éxito y error con el texto exacto del `requirements.md`, mostrados junto al formulario que los originó. No inventes textos nuevos. (origen: D-07 · RN-010)
8. Usá HTML, CSS y JavaScript sin frameworks en el navegador, PHP en el servidor y MySQL como almacén. No agregues librerías, gestores de paquetes ni herramientas de compilación. (origen: D-09)
9. Usá los nombres del dominio del `requirements.md` (comentario, respuesta, autor, contenido, fecha de creación), sin sinónimos ni abreviaturas propias. (origen: requirements.md §2 y §3)
10. Cambiá la estructura de la base solo con scripts versionados en `sql/`; el código de la app nunca la modifica mientras atiende pedidos. (origen: design.md §3, "Esquema y migraciones" · §4)

## Reglas acotadas por carpeta

Las reglas que solo valen para el servidor están en [`api/AGENTS.md`](api/AGENTS.md) y se
aplican cuando trabajás dentro de `api/` (consultas parametrizadas, operaciones permitidas,
formato de respuesta). No las repitas acá.

## Lo que NO tenés que hacer

- No agregues funcionalidad fuera de alcance sin un requisito previo en `requirements.md`: likes, reacciones, búsqueda, filtros, paginación, login, edición, borrado, tiempo real, varias conversaciones. (origen: requirements.md §6)
- No expongas ninguna operación de borrado o edición, aunque la interfaz no tenga botón; F-MOD-001 está diferida. (origen: D-08, D-10 · RN-009, RN-011)
- No implementes lógica cuando la tarea pide solo estructura.
- No resuelvas por tu cuenta las dudas de `design.md` §5: preguntá.
- No modifiques `specs/` salvo que te lo pidan explícitamente.
- No hagas commit ni push sin que te lo pidan.

## Cómo verificar

- Al cerrar una tarea, decí qué criterios cubre (por ejemplo, `F-COM-001 · CA-3`) y cuáles no
  pudiste verificar. Todavía no hay pruebas automáticas: si un caso no tiene prueba, señalalo
  en vez de darlo por cubierto.
- Entorno local: XAMPP (Apache en el puerto 8080, MySQL con usuario `root` sin contraseña). La
  app se abre en http://localhost:8080/app-comentarios-sdd/index.html y la base se crea desde
  `sql/schema.sql`.
- En los mensajes de commit mencioná los IDs que toca el cambio (F-xxx, RN-xxx, D-xx).
