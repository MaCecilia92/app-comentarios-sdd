# design.md — App de Comentarios

- **Feature:** `001-publicar-comentarios`
- **Versión del documento:** 1.2
- **Estado:** APROBADO — base del andamiaje de la Semana 5
- **Semana:** 4 — SDD II (Spec Anchored)
- **requirements.md de referencia:** [`requirements.md`](./requirements.md) (misma carpeta, v1.2)

> **Este documento es el CÓMO.** Registra decisiones de diseño y su porqué, trazadas contra los
> IDs del `requirements.md` (formato `F-COM-001 · CA-3` o `RN-001`).
> **No contiene código de implementación**, ni nombres exactos de funciones o archivos, ni la
> definición de tablas: eso aterriza en la Semana 5.
>
> **Regla de calibración:** las decisiones de las secciones 1, 3, 4 y 5 tienen que seguir
> siendo válidas si se cambia el lenguaje de programación. La única decisión atada a una
> tecnología concreta es D-09, y está aislada a propósito.

---

## 1. Arquitectura general

El sistema es una **aplicación cliente-servidor de dos piezas** que se comunican por una
interfaz de red explícita. La **interfaz**, que corre en el navegador del lector, muestra la
conversación y los formularios, y envía cada publicación a la **capa de aplicación**; nunca
accede a los datos por su cuenta. La capa de aplicación es el **único lugar donde se aplican
las reglas de negocio** (validación, estructura de un nivel, orden) y la única que habla con el
**almacén de datos relacional**, donde se persiste la conversación. La comunicación es **sin
estado**: no hay sesiones ni identidad de usuario, porque el autor es solo un nombre declarado
(RN-003) y la lectura es pública (RN-007). Después de cada publicación, la interfaz vuelve a
pedir la conversación completa: lo que se muestra es siempre lo que el servidor acaba de
devolver.

<!-- corregido por: comparación con el ejemplo de la cátedra (regla de calibración). El primer
borrador de este párrafo nombraba el lenguaje del servidor y el motor de base de datos; se
sacaron de la arquitectura general y se aislaron en D-09, para que esta sección sobreviva a un
cambio de tecnología. -->

---

## 2. Decisiones técnicas (ADR)

### D-01 — Las reglas de validación se aplican siempre en la capa de aplicación

- **Contexto:** varios criterios exigen rechazar publicaciones inválidas sin guardar nada
  (contenido fuera de 3 a 500 caracteres, vacío, autor fuera de 2 a 100). El código actual
  valida en el navegador y solo parcialmente en el servidor: por ejemplo, un nombre con solo
  espacios puede llegar a guardarse vacío.
- **Decisión:** toda regla de RN-001, RN-002 y RN-003 se verifica en la capa de aplicación
  **antes de cualquier escritura**, sin excepción. La interfaz puede anticipar las mismas
  reglas para avisar sin esperar al servidor, pero ese aviso es una cortesía y nunca la
  garantía.
- **Alternativas consideradas:** (a) validar solo en la interfaz: descartada porque cualquier
  cliente que hable directo con la capa de aplicación (una herramienta de línea de comandos,
  una prueba automática, una versión futura) se saltearía las reglas; (b) validar solo en el
  servidor: descartada porque el lector esperaría la ida y vuelta para enterarse de un error
  evidente, como el campo vacío.
- **Justificación:** un criterio de aceptación que se cumple según qué cliente lo invoque no
  está cumplido. La validación en el servidor es la única versión de la regla que sigue siendo
  cierta con un segundo cliente; la de la interfaz queda como mejora de experiencia.
- **Requisito relacionado:** F-COM-001 · CA-3, CA-4, CA-5, CA-6 · F-RES-001 · CA-3 · RN-001,
  RN-002, RN-003

> **Modificada por D-13 (Semana 5):** en la v1 la interfaz ya **no** anticipa la validación;
> la validación en la capa de aplicación sigue igual. Se conserva el texto original de D-01
> para no reescribir la historia de la decisión.

### D-02 — La longitud se mide en caracteres, después de recortar los espacios de los extremos

- **Contexto:** RN-001 y RN-003 hablan de caracteres y aclaran que "á" cuenta como uno. El
  código actual mide la longitud en bytes, así que un texto con acentos o emojis alcanza el
  límite antes de tiempo. Además, las reglas dicen que los espacios de los extremos no cuentan.
- **Decisión:** la capa de aplicación primero **recorta** los espacios al principio y al final
  del autor y del contenido, después **mide en caracteres** (no en bytes) y recién entonces
  valida. Lo que se guarda es el texto ya recortado.
- **Alternativas consideradas:** (a) medir en bytes: descartada porque contradice la definición
  de "carácter" del glosario; (b) validar sin recortar y recortar solo al mostrar: descartada
  porque "   " pasaría la regla de no vacío y "  Ana  " se guardaría distinto de como se ve;
  (c) recortar también los espacios internos repetidos: descartada porque ninguna regla lo
  pide y alteraría lo que el lector escribió.
- **Justificación:** el orden recortar → medir → validar → guardar hace que la regla se
  verifique sobre exactamente el mismo texto que después se muestra. Medir en caracteres hace
  que el límite de 500 sea el mismo para cualquier idioma.
- **Requisito relacionado:** F-COM-001 · CA-2, CA-5, CA-6, CA-7 · RN-001, RN-002, RN-003

### D-03 — Comentario y respuesta se persisten como dos entidades, con la respuesta ligada a un comentario existente

- **Contexto:** el `requirements.md` define dos entidades (Comentario y Respuesta), una
  respuesta pertenece a exactamente un comentario (RN-005) y no se puede responder a una
  respuesta (RN-004). Los intentos de la Semana 1 lo modelaron con una sola colección y una
  referencia opcional al padre, y terminaron permitiendo anidamiento infinito y respuestas
  huérfanas.
- **Decisión:** dos colecciones separadas en el almacén. Cada respuesta guarda una referencia
  obligatoria a un comentario, y el almacén garantiza la **integridad referencial**: no se
  puede guardar una respuesta cuyo comentario no exista. Como en la v1 nada se borra (RN-009),
  la relación se declara **restrictiva**: el almacén impide borrar un comentario que tenga
  respuestas.
- **Alternativas consideradas:** (a) una sola colección con referencia opcional al padre:
  descartada porque "responder a una respuesta" se podría guardar, y RN-004 dependería de que
  cada parte del código se acuerde de chequearlo; (b) un árbol genérico de nodos: descartada
  porque ofrece niveles infinitos, justo lo que la spec prohíbe; (c) borrado en cascada de las
  respuestas: descartada porque la v1 no tiene borrado y la cascada borraría datos en silencio
  si alguien lo hiciera por fuera de la app.
- **Justificación:** con dos colecciones, una respuesta a una respuesta **no se puede
  representar**: RN-004 se cumple por la forma del modelo y no por disciplina. La integridad
  referencial hace lo mismo con RN-005. La relación restrictiva protege RN-009 desde el
  almacén.
- **Requisito relacionado:** F-RES-001 · CA-2, CA-4, CA-5 · RN-004, RN-005, RN-009

<!-- corregido por: comparación con el ejemplo de la cátedra. El ejemplo declara la relación
con borrado en cascada, pero en mi requirements.md RN-009 dice que en la v1 nada se borra: la
cascada no tiene requisito que la origine y además borraría datos en silencio. Se cambió por
una relación restrictiva. -->

### D-04 — El orden se calcula en la capa de aplicación en cada lectura, con el identificador como desempate

- **Contexto:** RN-006 pide comentarios del más nuevo al más viejo, respuestas del más viejo al
  más nuevo, y que dos publicaciones con la misma fecha y hora respeten el orden de
  publicación. Los tres comentarios de ejemplo de la base actual tienen exactamente la misma
  fecha y hora, así que hoy su orden depende del azar del almacén.
- **Decisión:** la capa de aplicación entrega la conversación **ya ordenada** en cada lectura:
  primero por fecha y hora de publicación y, ante un empate, por el identificador, que crece
  con cada publicación. No se guarda ningún campo de posición, y la interfaz no reordena.
- **Alternativas consideradas:** (a) guardar una posición precalculada por registro: descartada
  porque es un dato más que puede contradecir a los demás; (b) ordenar solo por fecha y hora:
  descartada porque con empates el resultado no es estable; (c) que la interfaz ordene lo que
  recibe: descartada porque dos clientes podrían ordenar distinto y la regla quedaría
  duplicada.
- **Justificación:** el orden es una vista de los datos, no un dato. Calcularlo en un solo lugar
  y con un desempate determinista hace que dos lecturas de la misma conversación muestren
  siempre lo mismo.
- **Requisito relacionado:** F-COM-002 · CA-1, CA-2 · F-RES-001 · CA-2 · RN-006

### D-05 — El texto se guarda tal como se escribió y se neutraliza al mostrarlo

- **Contexto:** RN-008 exige que el autor y el contenido se vean exactamente como se
  escribieron, con sus saltos de línea, y que ningún texto se interprete como formato o
  código.
- **Decisión:** el almacén guarda el texto recortado (D-02) sin ninguna otra transformación. La
  **interfaz** lo inserta siempre como **texto literal**, nunca como marcado, y respeta los
  saltos de línea en la presentación. Los accesos al almacén usan siempre consultas
  parametrizadas: el texto del lector nunca forma parte de una instrucción.
- **Alternativas consideradas:** (a) limpiar o escapar el texto al recibirlo: descartada porque
  altera el dato que escribió el lector y reparte la defensa entre dos capas; (b) convertir los
  saltos de línea en marcado al guardar: descartada porque mezcla presentación con dato y
  rompería el texto si cambia la interfaz; (c) permitir un subconjunto de formato: descartada
  porque está fuera del alcance y multiplica el riesgo.
- **Justificación:** guardar el dato original y neutralizarlo en el único punto donde se
  convierte en pantalla es la defensa más simple de mantener y la que sobrevive a un cambio de
  interfaz.
- **Requisito relacionado:** F-COM-001 · CA-8, CA-9 · F-COM-002 · CA-1 · RN-008

### D-06 — Después de publicar, la interfaz vuelve a pedir la conversación completa

- **Contexto:** F-COM-002 · CA-5 exige que la lista y el contador se actualicen sin recargar la
  página. El identificador, la fecha y hora definitivas y el lugar en el orden los conoce solo
  la capa de aplicación.
- **Decisión:** la interfaz **no mantiene una copia propia de la conversación**. Después de una
  publicación exitosa pide de nuevo la conversación completa y reemplaza lo que muestra,
  contador incluido. El contador se deriva de lo recibido y cuenta solo comentarios.
- **Alternativas consideradas:** (a) insertar en la lista lo recién publicado sin volver a
  preguntar: descartada porque obliga a replicar en el cliente la regla de orden (D-04) y
  porque la fecha y hora definitivas las asigna el servidor; (b) actualizar la lista en tiempo
  real cuando otro lector publica: descartada por alcance (ver sección 4).
- **Justificación:** si lo que se ve es siempre lo que el servidor acaba de devolver, no hay
  estado local que pueda quedar desactualizado. El costo, una lectura extra por publicación,
  es irrelevante con el volumen de la v1.
- **Requisito relacionado:** F-COM-002 · CA-4, CA-5 · F-RES-001 · CA-2 · RN-006

### D-07 — La capa de aplicación responde con el mensaje de negocio exacto, y la interfaz lo muestra junto al formulario que lo originó

- **Contexto:** la spec fija textos literales para cada rechazo y RN-010 define dónde y por
  cuánto tiempo se muestran. El código actual muestra todos los mensajes arriba de la página,
  incluso los de una respuesta.
- **Decisión:** ante un rechazo, la capa de aplicación devuelve **el motivo con el texto exacto
  de la spec**. La interfaz lo muestra **junto al formulario que hizo el envío** (el de nuevo
  comentario o el de esa respuesta): los de éxito durante 3 segundos y los de error hasta el
  siguiente envío. Si la capa de aplicación no responde, la interfaz muestra el mensaje de
  error de carga o de publicación de la spec.
- **Alternativas consideradas:** (a) que la interfaz traduzca códigos de error a textos propios:
  descartada porque habría dos fuentes de verdad para los mensajes; (b) una única zona de
  mensajes para toda la página: descartada porque contradice RN-010.
- **Justificación:** el texto del rechazo vive en el mismo lugar que la regla que lo produce
  (D-01), así que no pueden divergir. Ubicar el mensaje según el origen hace verificable
  RN-010.
- **Requisito relacionado:** F-COM-001 · CA-1, CA-3, CA-4, CA-5, CA-6 · F-COM-002 · CA-6 ·
  F-RES-001 · CA-3, CA-5 · RN-010

### D-08 — La interfaz de red no expone ninguna operación de borrado ni de edición

- **Contexto:** RN-009 dice que en la v1 nada se edita ni se borra, y el alcance deja la
  edición y el borrado para F-MOD. Sin embargo, el código actual expone una operación de
  borrado **sin ningún control**: cualquiera que conozca el identificador puede borrar un
  comentario.
- **Decisión:** la capa de aplicación ofrece solo dos operaciones de escritura (publicar
  comentario y publicar respuesta) y una de lectura (obtener la conversación). La operación de
  borrado existente **se elimina** y no se agrega edición.
- **Alternativas consideradas:** (a) mantener el borrado sin mostrar el botón en la interfaz:
  descartada porque una regla que solo depende de esconder un botón no se cumple; (b)
  protegerlo con una clave fija: descartada porque inventa un rol moderador que la v1 no
  define.
- **Justificación:** la forma más barata de cumplir una prohibición es que la operación no
  exista. Cuando llegue F-MOD-001, el borrado entra con su propio requisito, su rol y su
  verificación (ver D-10).
- **Requisito relacionado:** RN-009 · `requirements.md` §6 (FUERA del alcance: edición y
  borrado) · §7 pendiente 8

### D-09 — Se conserva el stack del proyecto base: navegador sin frameworks, servidor PHP y base relacional MySQL locales

- **Contexto:** el `requirements.md` dejó como pendiente decidir el stack (§7, pendiente 1). El
  proyecto base de la cátedra ya corre en un entorno local (XAMPP) con HTML, CSS y JavaScript
  sin frameworks en el navegador, PHP en el servidor y MySQL como almacén.
- **Decisión:** se mantiene ese stack para la v1, sin frameworks ni bibliotecas externas, sobre
  el entorno local.
- **Alternativas consideradas:** (a) un framework de interfaz para una aplicación de una sola
  página: descartada porque agrega herramientas de compilación y dependencias para una sola
  pantalla con dos formularios; (b) cambiar el lenguaje del servidor: descartada porque obliga
  a rehacer el entorno local de toda la cursada sin que ningún requisito lo pida; (c) un
  almacén embebido en archivo: descartada porque D-03 se apoya en la integridad referencial
  del almacén, que con el entorno actual ya está disponible.
- **Justificación:** ningún requisito exige un cambio de tecnología, y el entorno ya está
  funcionando. Todas las demás decisiones de este documento son independientes del stack: si
  esta cambia, solo se reescribe este ADR.
- **Requisito relacionado:** `requirements.md` §1 (contexto) · §7 pendiente 1 · RN-007

<!-- corregido por: comparación con el ejemplo de la cátedra. El ejemplo deja el stack
implícito; en mi requirements.md la decisión de stack quedó explícitamente pendiente (§7.1),
así que se registró como ADR con alternativas en lugar de darla por sobreentendida. -->

### D-10 — El rol moderador queda declarado en la spec; la v1 lo respeta sin login porque no expone ningún borrado, y la verificación del rol se difiere

- **Contexto:** RN-011 dice que solo un moderador puede borrar, y F-MOD-001 especifica cómo,
  pero la v1 no tiene autenticación: no hay forma de saber si quien pide un borrado es un
  moderador. El código actual tiene un borrado sin ningún control, que cualquiera puede usar.
  La pregunta es cómo se respeta una regla de permisos sin poder identificar a nadie.
- **Decisión:** en la v1 el rol moderador **queda declarado en la spec** (glosario, RN-011,
  F-MOD-001) y **la verificación del rol se difiere** a la versión que construya la
  autenticación, a partir de la Semana 5. Mientras tanto la regla se respeta **por ausencia**:
  la capa de aplicación no ofrece ninguna operación de borrado (D-08), así que nadie puede
  borrar, y "solo un moderador puede borrar" se cumple sin excepciones. Cuando se habilite
  F-MOD-001, el borrado entra **junto con** su verificación de rol, nunca antes.
- **Alternativas consideradas:** (a) construir login y cuentas en la v1: descartada porque la
  autenticación está FUERA del alcance del `requirements.md` y obligaría a decidir cuentas,
  contraseñas y sesiones que ninguna feature de la v1 pide; (b) una clave fija de moderador
  compartida, guardada en la configuración: descartada porque inventa una autenticación débil
  que la spec no define, se filtra con facilidad y no permite saber qué moderador borró; (c)
  mantener el borrado y solo esconder el botón a los lectores (lo que hace hoy el código):
  descartada porque contradice F-MOD-001 · CA-4, ya que cualquiera podría borrar por fuera de
  la interfaz; (d) que el moderador borre directamente en el almacén con las herramientas del
  entorno local: descartada como mecanismo de la app porque deja la regla fuera del sistema y
  sin los mensajes de F-MOD-001, aunque queda como recurso operativo de emergencia (ver
  sección 5).
- **Justificación:** una regla de permisos se puede cumplir de dos maneras: verificando quién
  pide, o no ofreciendo la acción. Sin autenticación, la única forma honesta es la segunda.
  Declarar el rol desde ahora deja claro el contrato para la versión que lo implemente, y
  diferir la verificación evita una solución a medias que después habría que desarmar.
- **Requisito relacionado:** RN-011, RN-009 · F-MOD-001 · CA-4 · `requirements.md` §6 (FUERA
  del alcance: autenticación y borrado por moderación)

<!-- D-11 a D-13 se agregaron en la Semana 5, al revisar el plan del andamiaje: resuelven las
dudas 4, 2 y 5 de la sección 5, que se habían decidido en la sesión de Plan Mode pero quedaban
escritas solo en el plan. -->

### D-11 — La base se regenera desde el esquema nuevo, sin migrar los datos existentes

- **Contexto:** la base del proyecto base tiene una sola colección, sin respuestas, y sus tres
  comentarios de ejemplo comparten fecha y hora. En los intentos de la Semana 1 hubo bases con
  columnas que esta spec no reconoce. D-03 exige dos colecciones con integridad referencial.
- **Decisión:** el script de esquema **borra la base existente y la crea de nuevo** con las dos
  colecciones de D-03. La base nueva arranca **vacía**, sin datos de ejemplo. El encabezado del
  script advierte que es destructivo.
- **Alternativas consideradas:** (a) migrar la base existente con un script de cambios:
  descartada porque obliga a contemplar las variantes de esquema que dejaron los intentos de la
  Semana 1, para conservar tres comentarios de ejemplo sin valor; (b) regenerar pero cargar
  datos de ejemplo: descartada porque los ejemplos actuales comparten fecha y hora, y una base
  vacía permite verificar desde el primer momento el estado vacío de F-COM-002 · CA-3.
- **Justificación:** no hay datos reales que preservar, y un único script que crea todo desde
  cero es más fácil de verificar que una migración. Si en el futuro hay datos que conservar,
  los cambios de esquema irán como migraciones versionadas (design.md §3, "Esquema y
  migraciones").
- **Requisito relacionado:** D-03 · F-COM-002 · CA-3 · `requirements.md` §7 pendiente 8

### D-12 — La fecha y hora se guardan en UTC y la interfaz las muestra en la hora local del lector

- **Contexto:** RN-003 exige registrar la fecha y hora de cada publicación, y D-04 las usa para
  ordenar. La spec no decía en qué zona horaria se guardan ni cómo se muestran.
- **Decisión:** la capa de aplicación registra el instante de publicación en **UTC**,
  independientemente de la zona del servidor. La interfaz lo convierte a la **hora local del
  lector** al mostrarlo.
- **Alternativas consideradas:** (a) guardar en la hora local del servidor: descartada porque
  el resultado depende de la configuración del entorno y cambia si la base se mueve a otra
  máquina; (b) mostrar siempre la hora de Argentina: descartada porque un lector en otra zona
  vería una hora que no es la suya, y la spec no fija una zona.
- **Justificación:** una referencia única hace que el orden de D-04 no dependa del entorno, y
  convertir solo al mostrar mantiene el dato guardado sin ambigüedad.
- **Requisito relacionado:** RN-003 · RN-006 · F-COM-001 · CA-1 · F-COM-002 · CA-1, CA-2 ·
  `requirements.md` §7 pendiente 6

### D-13 — Los textos de los mensajes viven solo en la capa de aplicación; en la v1 la interfaz no anticipa la validación (modifica D-01)

- **Contexto:** D-01 permitía que la interfaz anticipara la validación como cortesía, y D-07
  pone los textos exactos de los mensajes en la capa de aplicación. Para anticipar con el texto
  de la spec (RN-010), la interfaz necesitaría una copia de esos textos, que puede divergir.
  Anticipar sin textos tampoco sirve: no habría mensaje que cumpla RN-010. Esto incluye la
  validación nativa del navegador (campos obligatorios), que muestra textos propios del
  navegador.
- **Decisión:** en la v1 la interfaz **no valida nada antes de enviar**: no anticipa reglas y
  no usa la validación nativa del navegador. Envía siempre y muestra el motivo que devuelve la
  capa de aplicación, junto al formulario que lo originó (D-07). Los textos existen en un solo
  lugar. D-01 queda modificada en su parte de cortesía; la validación en el servidor no cambia.
  Única excepción: cuando la capa de aplicación **no responde**, la interfaz muestra el mensaje
  de error de carga de F-COM-002 · CA-6, porque en ese caso no hay ningún motivo que recibir.
- **Alternativas consideradas:** (a) copiar los textos en la interfaz: descartada porque dos
  copias del mismo texto terminan divergiendo; (b) que la interfaz pida el catálogo de textos
  al servidor al cargar: descartada porque agrega una cuarta operación que la spec no pide
  (D-08 fija tres); (c) anticipar sin textos propios o con los del navegador: descartada porque
  contradice RN-010.
- **Justificación:** el costo es un viaje al servidor para errores evidentes como el campo
  vacío, irrelevante en un entorno local. A cambio, cada mensaje que ve el lector es
  exactamente el de la spec y sale del mismo lugar que la regla que lo produce.
- **Requisito relacionado:** RN-010 · D-01 · D-07 · F-COM-001 · CA-3, CA-4, CA-5, CA-6 ·
  F-RES-001 · CA-3

---

### Matriz de trazabilidad (requisito → decisión)

| Requisito | Decisiones | ¿Cubierto? |
|---|---|---|
| RN-001 Límite de contenido | D-01, D-02 | Sí |
| RN-002 Validez (no vacío) | D-01, D-02 | Sí |
| RN-003 Autoría y fecha | D-01, D-02, D-12 | Sí |
| RN-004 Un solo nivel | D-03 | Sí |
| RN-005 Padre existente | D-03 | Sí |
| RN-006 Orden | D-04, D-06 | Sí |
| RN-007 Lectura pública | D-09, sección 4 (sin autenticación) | Sí |
| RN-008 Texto literal | D-05 | Sí |
| RN-009 Inmutabilidad | D-03, D-08, D-10 | Sí |
| RN-010 Mensajes | D-07, D-13 | Sí |
| RN-011 Borrado solo por moderador | D-08, D-10 | Sí (por ausencia de borrado en la v1) |
| F-COM-001 · CA-1 | D-06, D-07, D-12 | Sí |
| F-COM-001 · CA-2, CA-7 | D-02 | Sí |
| F-COM-001 · CA-3, CA-4, CA-5, CA-6 | D-01, D-02, D-07, D-13 | Sí |
| F-COM-001 · CA-8, CA-9 | D-05 | Sí |
| F-COM-002 · CA-1, CA-2 | D-04, D-05, D-12 | Sí |
| F-COM-002 · CA-3 | D-11 (base vacía para verificarlo) | Sí |
| F-COM-002 · CA-4, CA-5 | D-06 | Sí |
| F-COM-002 · CA-6 | D-07 | Sí |
| F-RES-001 · CA-1, CA-6, CA-7 | — | Comportamiento de la interfaz, sin decisión técnica |
| F-RES-001 · CA-2 | D-03, D-04, D-06 | Sí |
| F-RES-001 · CA-3 | D-01, D-07, D-13 | Sí |
| F-RES-001 · CA-4 | D-03 | Sí |
| F-RES-001 · CA-5 | D-03, D-07 | Sí |
| F-MOD-001 · CA-4 | D-08, D-10 | Sí: nadie puede borrar en la v1 |
| F-MOD-001 · CA-1, CA-2, CA-3, CA-5 | D-10 | Diferidos: la feature está fuera del alcance de la v1 |

**Lectura de la matriz:** las once reglas de negocio tienen al menos una decisión que las
sostiene. F-MOD-001 está especificada pero diferida: su único criterio vigente en la v1 (CA-4,
nadie sin permiso puede borrar) se cumple por D-08 y D-10. Los criterios sin decisión son de pura interfaz (abrir, cerrar o cancelar un
formulario, el texto del estado vacío): se implementan tal como dice el `requirements.md`, sin
nada que decidir. Ninguna decisión de este documento queda sin requisito.

---

## 3. Estructura de módulos

Componentes lógicos y su responsabilidad. Los nombres de archivos y carpetas se deciden al
implementar.

- **Interfaz de conversación** — muestra la lista con su contador, el formulario de nuevo
  comentario, el formulario de respuesta de cada comentario y los mensajes junto a cada
  formulario. No decide reglas ni anticipa validaciones (D-13): envía, muestra el motivo que
  devuelve el servidor (D-07), muestra la fecha y hora en la hora local del lector (D-12) y
  vuelve a pedir la conversación (D-06). Zona del
  `requirements.md`: F-COM-001, F-COM-002, F-RES-001 (todos sus criterios de interfaz).
- **Punto de entrada de la capa de aplicación** — recibe las tres operaciones (publicar
  comentario, publicar respuesta, obtener conversación), rechaza cualquier otra (D-08) y
  traduce cada resultado a una respuesta con su motivo. Zona: RN-009, RN-010.
- **Validación** — concentra RN-001, RN-002 y RN-003: recorta, mide en caracteres y valida
  (D-02). Lo usan los dos componentes de publicación, así que un comentario y una respuesta no
  pueden validarse distinto. Zona: F-COM-001 · CA-2 a CA-7, F-RES-001 · CA-3.
- **Publicación de comentarios** — valida y guarda un comentario nuevo con su fecha y hora.
  Zona: F-COM-001.
- **Publicación de respuestas** — valida, comprueba que el comentario padre exista y guarda la
  respuesta ligada a él (D-03). Zona: F-RES-001, RN-004, RN-005.
- **Consulta de conversación** — devuelve todos los comentarios con sus respuestas, ya
  ordenados (D-04). Solo lee. Zona: F-COM-002, RN-006.
- **Acceso a datos** — frontera única con el almacén. Usa siempre consultas parametrizadas
  (D-05) y no conoce reglas de negocio.
- **Configuración** — ubicación y credenciales del almacén en un único lugar, separado de la
  lógica.
- **Esquema y migraciones** — la definición del almacén y sus cambios se versionan como scripts
  aplicados a mano. La capa de aplicación **nunca** modifica la estructura del almacén mientras
  atiende pedidos (en los intentos de la Semana 1 lo hacía en cada petición).

**Fronteras:** la interfaz solo habla con el punto de entrada; los componentes de publicación y
consulta solo hablan con el almacén a través del acceso a datos; nadie cruza una frontera "por
comodidad". Un cruce nuevo es una decisión nueva y entra como ADR.

---

## 4. Fuera de alcance técnico

Enfoques evaluados y descartados para esta versión, con su motivo.

- **Caché del listado de la conversación** — se descartó porque la conversación cambia con cada
  publicación y habría que decidir cuándo invalidarlo; con el volumen de la v1, leer siempre
  cuesta menos que acertar la invalidación.
- **Paginación o carga incremental del listado** — se descartó porque está FUERA del alcance del
  `requirements.md` y D-06 vuelve a leer la conversación completa; si el volumen crece, entra
  como feature nueva.
- **Actualización en tiempo real (conexión persistente o consultas periódicas)** — se descartó
  porque el `requirements.md` la deja fuera del alcance de F-COM-002 y exigiría mantener estado
  de conexión en el servidor, contra la arquitectura sin estado.
- **Autenticación, sesiones y roles** — se descartó porque el autor es un nombre declarado
  (RN-003), la lectura es pública (RN-007) y la moderación está fuera del alcance; no hay nada
  que autorizar en la v1.
- **Borrado en cascada de respuestas** — se descartó por D-03: la v1 no borra (RN-009), y la
  cascada eliminaría respuestas en silencio si alguien borrara por fuera de la app.
- **Limpieza o escape del texto al guardarlo** — se descartó por D-05: altera el dato original
  y reparte la defensa entre dos capas.
- **Framework de interfaz o biblioteca de componentes** — se descartó por D-09: una sola
  pantalla con dos formularios no justifica dependencias ni herramientas de compilación.
- **Modificar la estructura del almacén desde la capa de aplicación en cada pedido** — se
  descartó porque mezcla la evolución del esquema con la atención de pedidos y fue una de las
  causas de inconsistencia de los intentos de la Semana 1; los cambios de esquema van como
  migraciones versionadas.

---

## 5. Dudas técnicas pendientes

Lo que no se pudo resolver con la información disponible. Se registra para retomarlo en la
Semana 5, no se inventa.

> **Resueltas en la Semana 5:** la zona horaria (D-12), la migración de la base actual (D-11) y
> dónde viven los textos de los mensajes (D-13) dejaron de ser dudas y se registraron como ADR.

- **¿Cómo se cuenta un emoji compuesto?** D-02 mide en caracteres, pero algunos emojis (una
  familia, una bandera, un tono de piel) se componen de varios caracteres Unicode y se ven como
  uno solo. El glosario dice que "un emoji cuenta como uno", y medir lo que el lector percibe
  como un símbolo es más costoso que contar caracteres Unicode. Hay que decidir cuál de las dos
  mediciones vale, y si se aplica igual en la interfaz y en el servidor.
- **¿Qué pasa con un doble clic en "Publicar"?** Si el lector envía dos veces el mismo
  formulario antes de recibir respuesta, hoy se guardarían dos comentarios idénticos. La spec
  no dice si eso es un duplicado a evitar o dos publicaciones válidas.
- **¿Cómo se va a reconocer a un moderador?** D-10 difiere la verificación del rol, pero no
  dice con qué: cuentas con contraseña, un listado de moderadores definido por el
  administrador, u otro mecanismo. La elección depende de cómo se defina la autenticación, que
  todavía no tiene feature propia.
- **¿Qué pasa con las respuestas al borrar un comentario?** F-MOD-001 · CA-1 dice que dejan de
  mostrarse, pero D-03 declaró la relación restrictiva justamente para que nada se borre en
  silencio. Hay que decidir si el borrado de un comentario borra también sus respuestas (y
  cómo se registra) o si se marca como oculto sin eliminar los datos (borrado lógico).
- **¿Hace falta registrar quién borró y cuándo?** F-MOD-001 no pide un historial visible, pero
  sin un registro interno no se puede revisar una moderación injusta.
- **Mientras F-MOD-001 esté diferida, ¿cómo se saca un comentario ofensivo urgente?** La única
  vía sería borrarlo directo en el almacén con las herramientas del entorno local (alternativa
  d de D-10). Hay que decidir si eso se acepta como procedimiento de emergencia y quién puede
  hacerlo.
- **¿Se restringe el origen de las peticiones?** El servidor actual acepta peticiones desde
  cualquier origen. Ningún requisito lo pide ni lo prohíbe, pero junto con D-08 conviene
  decidir si la capa de aplicación solo atiende a la propia interfaz.

---

*Documento vivo: cualquier cambio se propone como un commit sobre este archivo, con su motivo,
y en el mismo cambio se revisa la matriz de trazabilidad. Si una decisión deja de sostenerse,
se agrega un ADR nuevo que la reemplaza y se indica en el anterior; no se reescribe la
historia.*
