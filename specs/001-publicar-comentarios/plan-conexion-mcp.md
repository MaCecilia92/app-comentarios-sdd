# Plan de conexión MCP — App de Comentarios

- **Nombre y apellido:** María Cecilia Alegre
- **Fecha:** 19/11/2026
- **Host usado:** Claude Code (extensión de VS Code), Windows 10
- **Spec:** `specs/001-publicar-comentarios/requirements.md` (v1.2) y `design.md` (v1.3)

---

## 1. Conexión

| Dato | Valor |
|---|---|
| Servidor MCP | `app-comentarios-mysql`, declarado en `.mcp.json` en la raíz del repo |
| Paquete | `@benborla29/mcp-server-mysql@2.0.9`, instalado global (versión fija) |
| Transporte | stdio: el Host lanza el proceso local (`cmd /c mcp-server-mysql`) |
| Base | `app_comentarios` en `127.0.0.1:3306` (XAMPP: MariaDB 10.4.32) |
| Usuario | `app_comentarios_lectura@localhost` |
| Herramienta expuesta | una sola: `mysql_query`, de solo lectura |

**Verificación (paso 3).** Le pedí al agente que listara tablas y columnas usando solo la
herramienta MCP, sin pegarle el esquema. Para eso consultó `information_schema` y
`SHOW CREATE TABLE`:

| Tabla | Columna | Tipo | Nulo | Clave / extra |
|---|---|---|---|---|
| `comentario` | `id` | `int(10) unsigned` | NO | PK, auto_increment |
| | `autor` | `varchar(100)` utf8mb4 | NO | |
| | `contenido` | `varchar(500)` utf8mb4 | NO | |
| | `fecha_creacion` | `datetime` | NO | default `current_timestamp()` |
| `respuesta` | `id` | `int(10) unsigned` | NO | PK, auto_increment |
| | `comentario_id` | `int(10) unsigned` | NO | FK `fk_respuesta_comentario` → `comentario.id` |
| | `autor` | `varchar(100)` utf8mb4 | NO | |
| | `contenido` | `varchar(500)` utf8mb4 | NO | |
| | `fecha_creacion` | `datetime` | NO | default `current_timestamp()` |

Índices: `idx_comentario_orden (fecha_creacion, id)` e
`idx_respuesta_orden (comentario_id, fecha_creacion, id)`. No hay restricciones CHECK. Las dos
tablas son InnoDB con `utf8mb4_unicode_ci`.

Datos al momento de la consulta: 3 comentarios y 32 respuestas, todos restos de las corridas
del evaluador de la Semana 6.

**Criterio del paso 3:** la conexión responde. La comparación con phpMyAdmin la hago yo:
- [ ] Las tablas y columnas coinciden con las que muestra phpMyAdmin.

---

## 2. Seguridad

| Punto | Qué quedó definido |
|---|---|
| Usuario de la base | `app_comentarios_lectura@localhost`, exclusivo del agente. La app no lo usa: sigue con su propia configuración. Se crea con `sql/usuario_mcp_lectura.sql`. |
| Permisos | Solo `SELECT` sobre `app_comentarios.*`. `SHOW GRANTS` lo confirma: `USAGE` global y `SELECT` sobre esa base, nada más. |
| Defensa en el servidor MCP | `ALLOW_INSERT_OPERATION`, `ALLOW_UPDATE_OPERATION`, `ALLOW_DELETE_OPERATION` y `ALLOW_DDL_OPERATION` en `false` (el último se declaró después del desafío extra; antes dependía del valor por defecto del paquete). Es una segunda barrera: aunque el servidor dejara pasar una escritura, la base la rechaza por falta de permisos. |
| Permisos heredados de XAMPP | XAMPP le daba al usuario anónimo escritura sobre `test` y `test_*`, y MariaDB se lo aplicaba a cualquier usuario. Se quitaron con `sql/quitar_permisos_anonimos_test.sql`. |
| Credenciales | La contraseña vive solo en la variable de entorno `APP_COMENTARIOS_MYSQL_CLAVE`. `.mcp.json` la referencia como `${APP_COMENTARIOS_MYSQL_CLAVE}`, y ningún archivo del repo la contiene (tampoco el script que crea el usuario: la recibe en `@clave` al ejecutarlo). |
| Escrituras | Requieren aprobación humana. El agente no escribe por MCP ni con `root`: propone un script versionado en `sql/` y espera mi aprobación antes de ejecutarlo. |
| Reglas para el agente | Sección "Acceso a la base por MCP" del `AGENTS.md` (3 reglas, con origen RN-009 · D-08, D-10 · design.md §3). |

**Observaciones:**

- `SHOW GRANTS` devuelve el hash de la contraseña del usuario. Es un dato sensible: su salida no
  se pega en entregas ni en commits. En este documento figuran solo los permisos.
- La app sigue conectándose como `root` sin contraseña, el entorno local de XAMPP que fija el
  `AGENTS.md`. Queda fuera de esta actividad, pero es el punto más débil del entorno.

---

## 3. Mapeo spec ↔ esquema

### Lo que coincide

| Requisito | En la base |
|---|---|
| RN-004 · D-03: una respuesta no puede tener respuestas | `respuesta` no tiene ninguna referencia a otra respuesta, así que el anidamiento no se puede representar. |
| RN-005 · D-03: el comentario padre tiene que existir | `comentario_id NOT NULL` con FK a `comentario.id`. |
| RN-009 · D-03: la relación es restrictiva | La FK no declara `ON DELETE`. En MariaDB eso equivale a `RESTRICT`, que coincide con `sql/schema.sql`. |
| RN-006 · D-04: orden por fecha y desempate por id | Hay índices `(fecha_creacion, id)` y `(comentario_id, fecha_creacion, id)`. Hay 26 respuestas con fecha repetida, así que el desempate por id se está usando. |
| RN-001, RN-003 · D-02: máximos en caracteres | `varchar(500)` y `varchar(100)` en utf8mb4 cuentan caracteres, no bytes. Las respuestas guardadas llegan exactamente a 500 y 100. |
| RN-003: autoría y fecha obligatorias | `autor` y `fecha_creacion` son `NOT NULL`. |

### Brechas

Una entrada por brecha. Ninguna se corrigió: todas las acciones son propuestas.

**B-01 · Los mínimos y el "no vacío" no están en la base**
- **Requisito de origen:** RN-001, RN-002, RN-003 · D-01, D-02
- **Tipo:** restricción faltante
- **Qué pide la spec:** contenido de 3 a 500 caracteres y autor de 2 a 100, ninguno vacío ni
  solo con espacios.
- **Qué hay en la base:** solo `NOT NULL` y el largo máximo. Sin CHECK, la base acepta `''`,
  `'   '` o un contenido de 1 carácter.
- **Estado:** aceptada por diseño. D-01 pone la regla en la capa de aplicación, y la app la
  cumple (evaluador de la Semana 6: 20/20). El riesgo es una escritura por fuera de la app, por
  ejemplo con `root` desde phpMyAdmin.
- **Acción propuesta:** ninguna en la v1. Si se quiere una defensa en la base, hace falta un ADR
  nuevo y una migración en `sql/` con `CHECK (CHAR_LENGTH(TRIM(...)) BETWEEN ...)`. MariaDB
  10.4 aplica los CHECK. Decide el equipo, no el agente.

**B-02 · La base trunca en silencio en lugar de rechazar**
- **Requisito de origen:** F-COM-001 · CA-3 ("no se guarda nada") · RN-001
- **Tipo:** configuración del motor
- **Qué pide la spec:** un contenido de 501 caracteres o más no se guarda.
- **Qué hay en la base:** `sql_mode` es `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`,
  sin `STRICT_TRANS_TABLES`. Un `INSERT` con 501 caracteres no falla: se guardan los primeros
  500 con una advertencia. `api/acceso_datos.php` no fija el modo en la conexión.
- **Estado:** latente. Hoy no ocurre porque la app valida antes de escribir (D-01), pero si esa
  validación fallara, la base guardaría un texto recortado en lugar de rechazarlo.
- **Acción propuesta:** fijar el modo estricto en la conexión de la app, junto al
  `SET time_zone` de D-12 (cambio en `api/`, con su ID en el commit). No cambia la estructura,
  así que no necesita script en `sql/`.

**B-03 · El valor por defecto de la fecha está en hora local, no en UTC**
- **Requisito de origen:** D-12 · RN-003 · RN-006
- **Tipo:** valor por defecto inconsistente con una decisión
- **Qué pide el diseño:** `fecha_creacion` en UTC, sin depender de la zona del servidor.
- **Qué hay en la base:** `DEFAULT current_timestamp()` con zona `SYSTEM` =
  `America/Buenos_Aires`. Al consultar, `NOW()` daba 3 horas menos que `UTC_TIMESTAMP()`. La app
  no usa el default: abre la conexión con `+00:00` e inserta `UTC_TIMESTAMP()`.
- **Estado:** latente. Una fila insertada sin fecha por fuera de la app queda corrida 3 horas, y
  eso rompe el orden de D-04.
- **Acción propuesta:** una migración en `sql/` que quite el `DEFAULT`, para que la fecha sea
  siempre explícita, y el mismo cambio en `sql/schema.sql`. Espera aprobación.

**B-04 · Los nombres de la spec no coinciden con los de la base**
- **Requisito de origen:** `requirements.md` §3 · regla 9 del `AGENTS.md`
- **Tipo:** nomenclatura
- **Qué pide la spec:** `comentarioPadre` y `fechaCreacion`.
- **Qué hay en la base:** `comentario_id` y `fecha_creacion`, los mismos nombres que fija el
  contrato D-14.
- **Estado:** el diseño ya resolvió los nombres técnicos (D-14), pero `requirements.md` §3 no
  dice cómo se traducen. Es el equivalente del `padre_id` del enunciado: acá la columna existe,
  con otro nombre.
- **Acción propuesta:** no tocar el esquema. Decidir si se agrega una nota de correspondencia en
  `requirements.md` §3; necesita un pedido explícito, porque `specs/` no se modifica sin él.

**B-05 · El largo máximo depende de cómo se cuente un emoji compuesto**
- **Requisito de origen:** RN-001 · glosario ("un emoji cuenta como uno") · design.md §5
- **Tipo:** duda abierta con impacto en el tipo de columna
- **Qué pide la spec:** que un emoji cuente como un carácter.
- **Qué hay en la base:** `varchar(500)` cuenta puntos de código. Un emoji de familia o una
  bandera ocupa varios.
- **Estado:** pendiente de la duda de design.md §5. Si se decide contar lo que el lector ve
  como un símbolo, un texto de 500 símbolos visibles puede superar las 500 posiciones de la
  columna, y con B-02 se truncaría.
- **Acción propuesta:** resolver primero la duda y, después, si hace falta, ampliar la columna
  con una migración en `sql/`. No se resuelve acá.

**B-06 · La base no impide publicaciones duplicadas**
- **Requisito de origen:** design.md §5 (doble clic en "Publicar") · F-COM-001 · CA-1
- **Tipo:** duda abierta
- **Qué pide la spec:** nada todavía: no define si dos envíos idénticos son un duplicado.
- **Qué hay en la base:** ninguna restricción de unicidad ni clave de idempotencia.
- **Estado:** pendiente de decisión.
- **Acción propuesta:** ninguna hasta que se decida. Si se trata como duplicado, la solución
  probablemente vaya en la app y no en la base.

**B-07 · La relación restrictiva choca con F-MOD-001 · CA-1**
- **Requisito de origen:** F-MOD-001 · CA-1 (diferida) · D-03 · design.md §5
- **Tipo:** conflicto futuro entre la relación y una feature diferida
- **Qué pide la spec:** al borrar un comentario, sus respuestas dejan de mostrarse.
- **Qué hay en la base:** la FK restrictiva impide borrar un comentario que tenga respuestas.
- **Estado:** correcto para la v1 (RN-009: nada se borra). Se vuelve una brecha cuando se
  habilite F-MOD-001.
- **Acción propuesta:** ninguna en la v1. Cuando llegue F-MOD-001, elegir entre borrado lógico y
  borrado de las respuestas con registro (duda de design.md §5) y migrar en `sql/`.

**B-08 · No hay estructura para el moderador ni para el registro de borrados**
- **Requisito de origen:** RN-011 · F-MOD-001 · D-10 · design.md §5
- **Tipo:** tablas o columnas faltantes (diferidas)
- **Qué pide la spec:** un moderador reconocido y, posiblemente, un registro de quién borró y
  cuándo.
- **Qué hay en la base:** nada.
- **Estado:** diferida a propósito. Que no exista es justamente lo que pide D-10.
- **Acción propuesta:** ninguna hasta que haya feature de autenticación.

**B-09 · La base no está vacía**
- **Requisito de origen:** D-11 · F-COM-002 · CA-3
- **Tipo:** estado de los datos
- **Qué pide el diseño:** la base arranca vacía, para poder verificar el estado vacío.
- **Qué hay en la base:** 3 comentarios y 32 respuestas de las corridas del evaluador.
- **Estado:** no es un error del esquema, pero con estos datos no se puede verificar CA-3.
- **Acción propuesta:** antes de verificar CA-3, regenerar con `sql/schema.sql`. Es
  destructivo, así que lo ejecuto yo y no el agente.

**B-10 · El motor es MariaDB, no MySQL**
- **Requisito de origen:** D-09
- **Tipo:** entorno
- **Qué pide el diseño:** "MySQL como almacén".
- **Qué hay en la base:** MariaDB 10.4.32, la que trae XAMPP.
- **Estado:** compatible en todo lo que usa la app. Se nota en detalles como el `SHOW CREATE`
  que omite `RESTRICT` o el soporte de CHECK.
- **Acción propuesta:** una aclaración en D-09 ("MySQL o MariaDB, la de XAMPP"), solo si se
  pide modificar `specs/`.

---

## 4. Cierre

- **Conexión:** el servidor está registrado y responde. El agente leyó el esquema completo
  usando solo MCP.
- **Seguridad:** está declarada en el `AGENTS.md` y aplicada en la base (solo SELECT, sin
  escritura heredada en `test`) y en el servidor (operaciones de escritura desactivadas). Las
  credenciales están fuera del repo.
- **Mapeo:** el esquema cumple lo estructural de la spec (dos entidades, FK restrictiva,
  índices de orden). Las 10 brechas son de tres clases:
  - **Latentes:** B-01, B-02 y B-03. La app las cubre hoy, pero la base no las defiende.
  - **Dudas abiertas de design.md §5:** B-05 y B-06.
  - **Diferidas o de documentación:** B-04, B-07, B-08, B-09 y B-10.
- **Lo más urgente:** B-02 (modo estricto) y B-03 (fecha por defecto). Son baratas y cierran
  dos formas de que la base guarde datos que contradicen la spec sin avisar.
- **Sin verificar:**
  - la coincidencia con phpMyAdmin (paso 3), que hago yo;
  - que MariaDB rechace una escritura que llegue por MCP: en el desafío extra el servidor
    frenó todas antes (ver abajo);
  - `information_schema.REFERENTIAL_CONSTRAINTS`, que vuelve vacía para el usuario de lectura,
    así que la regla de la FK se dedujo de `SHOW CREATE TABLE`.

### Síntesis (paso 6)

Leer el esquema real antes de planificar me mostró que la base no defiende por sí sola varias
reglas que yo daba por cubiertas. La spec y el `schema.sql` decían "contenido de 3 a 500" y
"fecha en UTC", y la app lo cumple, pero la base acepta textos vacíos, guarda la fecha por
defecto en hora local y, sin modo estricto, recorta en silencio un texto de 501 caracteres en
lugar de rechazarlo. Planificando a ciegas, habría asumido que el `varchar(500)` y la conexión
en UTC alcanzaban.

La brecha que más me habría sorprendido en producción es B-02: si alguna vez falla la
validación de la app o alguien escribe por fuera de ella, la base guarda un comentario
recortado sin avisar, justo lo contrario de F-COM-001 · CA-3. También aprendí que la conexión
tiene riesgos que la spec no menciona: XAMPP le daba a cualquier usuario escritura sobre
`test`, así que un usuario "de solo lectura" no lo era hasta que lo revisé.

Sobre las escrituras, la seguridad quedó en dos niveles. Por MCP el agente no puede escribir
nunca: el usuario tiene solo SELECT y el servidor tiene las escrituras desactivadas. Si una
tarea necesita cambiar la base, el `AGENTS.md` lo saca de ese canal: el agente propone un
script versionado en `sql/` y yo lo apruebo antes de ejecutarlo. Así pasó en esta misma
actividad con `sql/quitar_permisos_anonimos_test.sql`: el agente encontró el problema, me
planteó las opciones y recién escribió y ejecutó el script cuando elegí una. En el formulario
marqué "solo lectura" porque la pregunta es sobre el servidor MCP, pero la aprobación humana
existe para todo lo que pase por fuera de él.

Desafío extra: elegí la prueba de escritura negada. Las cinco escrituras que le autoricé
intentar al agente por MCP se rechazaron y la base quedó igual. Lo más útil fue lo que la
prueba no demostró: todas las frenó el servidor, así que la barrera de la base no se ejercitó
por ese camino, y el bloqueo de CREATE TABLE dependía de un valor por defecto que no estaba
declarado.

### Desafío extra: escritura negada

Le pedí al agente, en la sesión que tiene el servidor MCP, que **intentara** cinco escrituras
solo por la herramienta `mysql_query`, que copiara el error textual de cada una y que no las
reintentara por otro camino si fallaban.

| # | Sentencia | Error textual | Quién lo rechazó |
|---|---|---|---|
| a | `INSERT INTO comentario …` | INSERT operations are not allowed for schema 'app_comentarios'. Ask the administrator to update SCHEMA_INSERT_PERMISSIONS. | Servidor MCP (`ALLOW_INSERT_OPERATION=false`) |
| b | `UPDATE comentario SET autor = 'Prueba' WHERE id = 1` | UPDATE operations are not allowed for schema 'app_comentarios'. Ask the administrator to update SCHEMA_UPDATE_PERMISSIONS. | Servidor MCP (`ALLOW_UPDATE_OPERATION=false`) |
| c | `DELETE FROM respuesta WHERE id = 1` | DELETE operations are not allowed for schema 'app_comentarios'. Ask the administrator to update SCHEMA_DELETE_PERMISSIONS. | Servidor MCP (`ALLOW_DELETE_OPERATION=false`) |
| d | `CREATE TABLE prueba_permiso (id INT)` | DDL operations are not allowed for schema 'app_comentarios'. Ask the administrator to update SCHEMA_DDL_PERMISSIONS. | Servidor MCP (DDL desactivado por defecto) |
| e | `CREATE TABLE test.prueba_permiso (id INT)` | DDL operations are not allowed for schema 'app_comentarios'. Ask the administrator to update SCHEMA_DDL_PERMISSIONS. | Servidor MCP (DDL desactivado por defecto) |

**Antes y después:** 3 comentarios y 32 respuestas en los dos momentos; el autor del
comentario 1 sigue siendo "Evaluador", la respuesta 1 sigue existiendo y no se creó ninguna
tabla `prueba_permiso`. El agente no reintentó nada por otro camino.

**Lo que demuestra y lo que no:**

- **Primera barrera (servidor MCP): demostrada.** Rechazó las cinco antes de mandarlas a la
  base.
- **Segunda barrera (permisos de MariaDB): no se ejercitó por MCP.** Ningún intento llegó a la
  base, así que no apareció el error 1142. La evidencia de esa barrera viene de pruebas
  directas con el cliente `mysql` y el usuario `app_comentarios_lectura`, hechas al configurar
  la conexión: INSERT, DELETE y DROP sobre `app_comentarios` dieron 1142, y después de
  `sql/quitar_permisos_anonimos_test.sql`, CREATE TABLE sobre `test` también dio 1142.
- **El bloqueo de DDL no estaba declarado.** Se frenó porque el paquete trae
  `ALLOW_DDL_OPERATION` desactivado por defecto. Con la prueba a la vista se agregó
  `"ALLOW_DDL_OPERATION": "false"` en `.mcp.json`, para no depender del default de una versión.
- **El intento (e) muestra un mensaje sobre la base equivocada.** Apuntaba a `test` y el error
  habla de `app_comentarios`: el servidor decide con la base de la conexión, no con la que
  nombra la sentencia. Esta vez el resultado fue correcto, pero ese chequeo no es una defensa
  confiable para otras bases; la que sí lo es son los permisos del usuario.
