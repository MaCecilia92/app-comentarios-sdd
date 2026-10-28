# Registro de configuración y despliegue — App de Comentarios

## 1. Herramienta y modo de trabajo

- Herramienta: Otra (Claude Code, extensión de VS Code). Es la herramienta con la que vengo trabajando el repo; lee las mismas reglas que OpenCode porque el `CLAUDE.md` importa el `AGENTS.md`.
- Modo para planificar: Plan Mode de Claude Code (Shift+Tab, el equivalente al Tab de OpenCode), en una sesión nueva. En ese modo el agente puede leer pero no editar archivos ni ejecutar comandos sin permiso.
- Modo para ejecutar: modo normal (con edición), en otra sesión de Claude Code, donde le pasé el plan y mis correcciones.
- En qué momento cambiaste de modo y por qué: no ejecuté desde la sesión del plan. Primero revisé el plan completo contra el `requirements.md` y el `design.md`, armé las correcciones y recién con el plan corregido pasé a ejecutar en otra sesión. Así la ejecución partió del plan revisado y no del original.

## 2. AGENTS.md destilado

El archivo está en la raíz del repo, versionado. Resumen de las reglas con su origen:

### Reglas

- Validá toda regla de negocio en el servidor antes de cualquier escritura; en la v1 el navegador no valida antes de enviar. (origen: D-01, D-13 · RN-001, RN-002, RN-003, RN-010)
- Recortá los espacios de los extremos y medí en caracteres, no en bytes: contenido de 3 a 500, autor de 2 a 100. (origen: D-02 · RN-001, RN-003)
- Modelá comentario y respuesta como dos entidades; una respuesta solo referencia un comentario existente. (origen: D-03 · RN-004, RN-005)
- Entregá la conversación ya ordenada desde el servidor, con el identificador como desempate. (origen: D-04 · RN-006)
- Mostrá todo texto del lector como texto literal, conservando los saltos de línea. (origen: D-05 · RN-008)
- Después de publicar, volvé a pedir la conversación completa. (origen: D-06 · F-COM-002 CA-5)
- Usá los mensajes con el texto exacto de la spec, junto al formulario que los originó. (origen: D-07 · RN-010)
- Usá HTML, CSS y JavaScript sin frameworks, PHP y MySQL, sin dependencias. (origen: D-09)
- Usá los nombres del dominio del `requirements.md`, sin sinónimos. (origen: RN-003 · requirements.md §2 y §3)
- Cambiá la estructura de la base solo con scripts en `sql/`, nunca desde la app. (origen: D-11 · design.md §3 y §4)

### Regla que acota alcance

- Las reglas que solo valen para el servidor están en un `AGENTS.md` dentro de `api/` (acotado por directorio): consultas preparadas con PDO, exactamente tres operaciones, mensajes exactos y nada de cambios de esquema desde la API (origen: D-05, D-08 · RN-009, RN-011). Además, `opencode.json` las declara en `instructions` para que OpenCode las cargue, y `api/CLAUDE.md` las importa para Claude Code.

### Lo que le pediste al agente que NO haga

- No agregues funcionalidad fuera de alcance (likes, búsqueda, paginación, login, edición, borrado, tiempo real) sin un requisito previo en `requirements.md` §6.
- No expongas ninguna operación de borrado o edición (D-08, D-10).
- No implementes lógica cuando la tarea pide solo estructura.
- No resuelvas por tu cuenta las dudas de `design.md` §5: preguntá.
- No modifiques `specs/` ni hagas commit o push sin que te lo pidan.

## 3. Prompt de Plan Mode

Antes del encargo hice el test de trazabilidad en la misma sesión del plan, con el prompt: "Antes de proponer nada, decime qué reglas vas a aplicar y de dónde sale cada una."

**Resultado del test:** la sesión era nueva y yo no le mostré las reglas, así que las tomó solo de los archivos del repo (el `CLAUDE.md` importa el `AGENTS.md`). Respondió:

- Que las reglas salen del `AGENTS.md` de la raíz y del de `api/` "cuando trabaje dentro de `api/`": reconoció la regla acotada por directorio.
- Que el QUÉ está en `requirements.md` y el CÓMO en `design.md`, y que si el código y la spec no coinciden "manda la spec".
- Las 10 reglas generales y las 7 de `api/`, cada una con su origen correcto (por ejemplo, "D-04 · RN-006" para el orden y "D-08 · RN-009, RN-011" para las tres operaciones). No inventó ninguna regla ni ningún origen.
- La lista completa de lo que no va a hacer, incluida "Dudas de design.md §5: no las resuelvo por mi cuenta; te pregunto".
- Cómo cierra cada tarea: qué criterios cubre (por ejemplo, F-COM-001 · CA-3) y cuáles no pudo verificar.

Un detalle confirma que leyó el archivo y no improvisó: citó la regla 1 como "la validación del navegador es solo un aviso anticipado", que era el texto vigente en ese momento. Esa regla cambió después, por la corrección 2 (D-13).

Después le pasé este encargo:

```
Leé specs/001-publicar-comentarios/requirements.md y specs/001-publicar-comentarios/design.md, y respetá el AGENTS.md de la raíz y el de api/.
Objetivo: proponer el andamiaje inicial de la App de Comentarios con los módulos de design.md §3.
Por ahora NO implementes comportamiento: estructura de carpetas y archivos ahora, lógica después. Los archivos quedan con un encabezado que diga su responsabilidad y la decisión D-xx que los origina; el esquema de la base va completo como script en sql/.
Tené en cuenta que ya existe código (api/comments.php, js/app.js, css/style.css, sql/schema.sql) que no sigue los módulos del design.md: proponé qué hacer con cada archivo y por qué.
En cada paso citá la regla del AGENTS.md o la decisión D-xx que aplicás.
Formato: lista de archivos (crear / modificar / eliminar) y lista de tareas numerada.
Si algo del diseño es ambiguo, preguntame antes de decidir.
```

Durante el plan, el agente me preguntó por cuatro dudas del `design.md` §5 (migración de la base, zona horaria, dónde viven los textos de los mensajes y nombres) y las respondí yo.

## 4. Revisión del plan

El plan era bueno en lo general: cubría los nueve módulos de `design.md` §3, eliminaba el borrado sin control y citaba en cada archivo la regla o la D-xx que aplicaba. Lo revisé con las cuatro preguntas del enunciado y encontré estas diferencias.

### Corrección 1

- Pedí cambiar: que mis respuestas a las dudas 2, 4 y 5 se registraran primero como ADR nuevos en el `design.md` (D-11 en adelante) y salieran de §5, antes de crear archivos.
- Motivo: las decisiones las tomé yo, pero quedaban escritas solo en el plan; el `design.md` seguía diciendo "pendiente". Es spec drift: los cambios bajan de la spec al código, no al revés.
- Resultado: se agregaron D-11 (la base se regenera vacía), D-12 (fecha y hora en UTC, mostradas en la hora local) y D-13 (textos de mensajes solo en el servidor). Las tres dudas salieron de §5 y la matriz de trazabilidad se actualizó.

### Corrección 2

- Pedí cambiar: que el navegador no anticipe la validación en la v1 y solo muestre el motivo que devuelve el servidor, registrado como un ADR que modifica D-01.
- Motivo: el plan decía que el navegador "anticipa sin textos propios", lo que contradice D-01 (que permitía anticipar como cortesía) y RN-010 (mensajes con el texto exacto). Así los textos viven en un solo lugar.
- Resultado: D-13 modifica D-01, que conserva su texto con una nota para no reescribir la historia. La regla 1 del `AGENTS.md` se alineó con D-13.

### Corrección 3

- Pedí cambiar: que no se renombre `css/style.css` a `estilos.css` ni se toquen las menciones a "Semana 1" del `index.html`.
- Motivo: el plan justificaba el renombrado con la regla 9, pero esa regla habla de los nombres del dominio, no de nombres de archivo; además dejaba las clases en inglés, así que era incoherente. Ningún requisito pedía esos cambios: el agente estiraba una regla para justificar algo que nadie pidió.
- Resultado: `style.css` conservó su nombre; solo se le agregó el encabezado y se le quitó la regla `.btn-delete` (D-08). El `index.html` mantiene sus textos.

### Corrección 4

- Pedí cambiar: que `acceso_datos.php` no abra ninguna conexión en el andamiaje y que la zona UTC quede descrita en el encabezado.
- Motivo: el plan decía que ese archivo "abre la conexión con la zona UTC", y eso ya es comportamiento; yo pedí estructura sin lógica.
- Resultado: `acceso_datos.php` tiene solo el encabezado y la inclusión de `config.php`.

## 5. Ejecución del andamiaje

Archivos y módulos que quedaron después de ejecutar el plan revisado (cada uno con encabezado de módulo, responsabilidad, D-xx, requisitos y fronteras):

- `api/conversacion.php` — punto de entrada; admite solo publicar comentario, publicar respuesta y obtener la conversación (D-08).
- `api/validacion.php` — único punto de validación (D-01, D-02, D-13).
- `api/publicacion_comentario.php` y `api/publicacion_respuesta.php` — publicación, con las inclusiones que marcan sus fronteras (D-03).
- `api/consulta_conversacion.php` — consulta ordenada, solo lectura (D-04).
- `api/acceso_datos.php` — frontera con el almacén, sin conexión todavía (D-05, D-12).
- `js/conversacion.js` — interfaz de conversación; reemplaza a `js/app.js`.
- `sql/schema.sql` — esquema completo con dos tablas, relación restrictiva e índices de orden; regenera la base vacía (D-03, D-04, D-11).
- Se modificaron `config.php`, `index.html` (zona de mensajes del formulario), `css/style.css` y `README.md`.
- Se eliminaron `api/comments.php` (tenía un borrado sin control) y `js/app.js`.
- Verificación: sintaxis PHP sin errores en los 7 archivos; la base rechazó una respuesta a un comentario inexistente (RN-005) y el borrado de un comentario con respuestas (RN-009); no quedó ninguna operación de borrado; la página carga sin errores 404. La app todavía no muestra la lista, como era esperable.

### Desvíos entre el plan revisado y lo que se generó

Hubo cuatro desviaciones respecto del plan revisado, todas por coherencia con la spec:

- Se quitaron los atributos de campo obligatorio del formulario: no estaban en el plan, pero D-13 prohíbe la validación nativa del navegador porque muestra textos propios.
- Se actualizó la regla 1 del `AGENTS.md`, que todavía hablaba de "aviso anticipado" y contradecía D-13.
- D-13 sumó una excepción: si el servidor no responde, la interfaz muestra el mensaje de error de carga de F-COM-002 CA-6.
- XAMPP trae MariaDB en lugar de MySQL 8: la relación se muestra sin la cláusula restrictiva porque es el comportamiento por defecto, y la prueba de integridad confirmó que funciona igual.

## 6. Contraste con el sprint de la Semana 1

Los problemas salen de las iteraciones del sprint que quedaron guardadas en las ramas `vibe-coding-fallido` y `vibe-coding-fallido-v2`: fue vibe coding sin estructura ni documentación.

- Problema que pasó en la Semana 1: el esquema de la base se armó por tres caminos que se contradecían (el script del esquema con relación entre tablas, una migración aparte y un cambio de estructura que la API ejecutaba en cada pedido, sin relación). Las respuestas huérfanas aparecían como comentarios principales.
- Regla que lo habría evitado: "Cambiá la estructura de la base solo con scripts en `sql/`" (regla 10) y la regla de `api/` que prohíbe cambios de esquema desde la API, junto con D-03 (dos entidades con integridad referencial).
- Evidencia de que hoy funciona: el esquema vive en un único script, la API no toca la estructura, y la base rechazó una respuesta huérfana en la verificación.
- Problema que pasó en la Semana 1: features fantasma. En la segunda iteración el agente agregó likes, búsqueda y cambio de orden que nadie había pedido, y las respuestas se podían anidar sin límite.
- Regla que lo habría evitado: "No agregues funcionalidad fuera de alcance sin un requisito previo" y RN-004 (respuestas de un solo nivel), más el Plan Mode, que obliga a ver la propuesta antes de que exista código.
- Evidencia de que hoy funciona: el plan no agregó ninguna feature fuera de la spec, y cuando el agente estiró una regla para justificar un cambio no pedido (renombrar `style.css`), lo frené en la revisión antes de ejecutar (corrección 3).
- Dónde tuve que frenar al agente hoy: en la revisión del plan, antes de cualquier archivo. Las cuatro correcciones fueron sobre el plan, no sobre código ya escrito.

## 7. Conclusión

- La diferencia con la Semana 1 no fue el agente, sino lo que lo rodea: reglas con origen, un plan revisado antes de ejecutar y una spec que manda. En mi próxima tarea voy a pedir siempre el plan primero, leerlo contra el `design.md` buscando decisiones que queden solo en el plan, y registrar en la spec cualquier decisión nueva antes de que llegue al código.
