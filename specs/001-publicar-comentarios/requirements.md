# requirements.md — App de Comentarios

- **Nombre del proyecto:** App de Comentarios
- **Versión del documento:** 1.0
- **Estado:** COMPLETO — listo para revisión en Semana 4
- **Semana:** 3 — SDD I (Spec First)

> **Nota:** este es el documento de especificación (el QUÉ).
> No contiene código, arquitectura ni decisiones técnicas: eso es `architect.md` (Semana 4).
> Todo lo que despierta un "¿cómo lo implemento?" está anotado en la sección 7.

---

## 1. Contexto y objetivos

La App de Comentarios es una página web donde cualquier lector puede publicar su opinión y
responder a la opinión de otros. Replica, a escala mínima y educativa, la sección de
comentarios de una publicación digital. Su objetivo es servir como proyecto base para
practicar el desarrollo guiado por especificaciones: cada comportamiento de la app tiene que
poder verificarse contra este documento.

<!-- corregido por: revisión contra el checklist SDD (punto 8). La plantilla mencionaba PHP,
MySQL y XAMPP en el contexto; eso es stack tecnológico y se movió a la sección 7. -->

---

## 2. Glosario

- **Lector:** cualquier persona que abre la app. No necesita registrarse ni identificarse.
- **Comentario:** texto publicado por un lector en la conversación principal de la app.
- **Respuesta:** texto publicado por un lector vinculado a un comentario existente (su
  *comentario padre*).
- **Comentario padre:** el comentario al que pertenece una respuesta.
- **Autor:** nombre que el lector escribe en el formulario al publicar. Es solo un nombre
  visible: no identifica a una cuenta ni a una persona verificada.
- **Conversación:** el conjunto de todos los comentarios con sus respuestas. En la v1 existe
  una única conversación.
- **Texto válido:** texto que, sin contar los espacios al principio y al final, tiene entre
  3 y 500 caracteres. Cada letra, número, signo, espacio interno, salto de línea o emoji
  cuenta como un carácter (una "á" cuenta como 1).
- **Artículo:** fuera del alcance de la v1. La app no muestra artículos ni separa
  conversaciones por artículo.
- **Moderador:** fuera del alcance de la v1. Se define cuando exista el dominio de
  moderación (F-MOD).

<!-- corregido por: la plantilla dejaba "Artículo" y "Moderador" sin definir. Se definen
explícitamente como fuera de alcance para que nadie (humano o agente) los implemente por su
cuenta. -->

---

## 3. Entidades del dominio

### Comentario

- **id** — identificador único del comentario.
- **autor** — nombre visible de quien lo escribió (2 a 100 caracteres).
- **contenido** — texto válido (3 a 500 caracteres).
- **fechaCreacion** — fecha y hora en que se publicó.

### Respuesta

- **id** — identificador único de la respuesta.
- **comentarioPadre** — el comentario al que responde.
- **autor** — nombre visible de quien la escribió (2 a 100 caracteres).
- **contenido** — texto válido (3 a 500 caracteres).
- **fechaCreacion** — fecha y hora en que se publicó.

### Relaciones

- Un Comentario puede tener cero, una o muchas Respuestas.
- Una Respuesta pertenece a exactamente un Comentario.
- Una Respuesta no puede tener Respuestas (1 solo nivel de profundidad).

---

## 4. Reglas de negocio (RN-xxx)

- **RN-001 — Límite de contenido.** El contenido de un comentario o respuesta tiene como
  mínimo 3 y como máximo 500 caracteres, sin contar los espacios al principio y al final.
- **RN-002 — Validez.** Nunca se guarda un comentario ni una respuesta cuyo contenido o autor
  esté vacío o tenga solo espacios.
- **RN-003 — Autoría y fecha.** Todo comentario y toda respuesta registra el nombre de autor
  escrito en el formulario (2 a 100 caracteres) y la fecha y hora de su publicación. El autor
  nunca se infiere del dispositivo ni del navegador.
- **RN-004 — Estructura de 1 nivel.** Solo se puede responder a un comentario. Nunca se
  responde a una respuesta.
- **RN-005 — Padre existente.** Una respuesta solo se guarda si su comentario padre existe en
  el momento de publicarla.
- **RN-006 — Orden.** Los comentarios se muestran del más nuevo al más viejo. Las respuestas
  de un comentario se muestran debajo de él, de la más vieja a la más nueva. Si dos
  publicaciones tienen la misma fecha y hora, se ordenan según el orden en que se publicaron.
- **RN-007 — Lectura pública.** Cualquier lector puede leer todos los comentarios y
  respuestas, sin registrarse.
- **RN-008 — Texto literal.** El autor y el contenido se muestran exactamente como fueron
  escritos: ningún texto ingresado se interpreta como formato, enlace o código.
- **RN-009 — Inmutabilidad en v1.** Una vez publicado, un comentario o respuesta no se edita
  ni se borra desde la app.

<!-- corregido por: inconsistencia del material. Las diapositivas 8 y 10 dicen "1 a 500
caracteres" y la lectura y la plantilla dicen "3 a 500". Se adopta 3 a 500 (RN-001) y se
aplica a comentarios y respuestas por igual. -->

---

## 5. Features (F-xxx-000)

### F-COM-001: Crear un comentario

**Historia de usuario:** Como lector, quiero publicar un comentario con mi nombre, para
compartir mi opinión con los demás lectores.

**Reglas que aplica:** RN-001, RN-002, RN-003, RN-008.

**Criterios de aceptación:**

- **CA-1. Camino feliz.** Dado que el formulario de nuevo comentario está visible, el autor
  tiene entre 2 y 100 caracteres y el contenido es un texto válido, cuando presiono
  "Publicar", entonces el comentario se guarda, aparece primero en la lista con su autor,
  contenido, fecha y hora, se muestra el mensaje "Comentario publicado correctamente" y el
  formulario queda vacío.
- **CA-2. Límite máximo aceptado.** Dado que el contenido tiene exactamente 500 caracteres,
  cuando presiono "Publicar", entonces el comentario se guarda completo, sin recortes.
- **CA-3. Límite máximo superado.** Dado que el contenido tiene 501 caracteres o más, cuando
  presiono "Publicar", entonces se muestra el mensaje "El comentario no puede superar los
  500 caracteres", no se guarda nada y el texto escrito permanece en el formulario.
- **CA-4. Límite mínimo.** Dado que el contenido tiene 1 o 2 caracteres, cuando presiono
  "Publicar", entonces se muestra el mensaje "El comentario debe tener al menos 3
  caracteres" y no se guarda nada.
- **CA-5. Vacío o solo espacios.** Dado que el contenido o el autor está vacío o tiene solo
  espacios, cuando presiono "Publicar", entonces se muestra el mensaje "Completá tu nombre y
  tu comentario" y no se guarda nada.
- **CA-6. Autor fuera de rango.** Dado que el autor tiene 1 carácter o más de 100, cuando
  presiono "Publicar", entonces se muestra el mensaje "El nombre debe tener entre 2 y 100
  caracteres" y no se guarda nada.
- **CA-7. Espacios en los extremos.** Dado que el contenido es "   Hola mundo   ", cuando
  presiono "Publicar", entonces se guarda y se muestra como "Hola mundo".
- **CA-8. Texto literal.** Dado que el contenido incluye texto con forma de etiqueta o
  código (por ejemplo, la palabra "negrita" encerrada entre los símbolos < y >), cuando se
  publica, entonces se muestra tal cual fue escrito, sin aplicar ningún formato.

**Fuera de alcance de esta feature:** edición y borrado de comentarios (F-MOD, versión
futura), likes y reacciones, formato de texto enriquecido, adjuntar imágenes o enlaces
clicables, recordar el nombre del autor entre visitas.

---

### F-COM-002: Listar comentarios

**Historia de usuario:** Como lector, quiero ver todos los comentarios con sus respuestas
apenas abro la app, para enterarme de la conversación y decidir si quiero participar.

**Reglas que aplica:** RN-006, RN-007, RN-008.

**Criterios de aceptación:**

- **CA-1. Orden.** Dado que existen comentarios, cuando abro la app, entonces se muestran
  todos, del más nuevo al más viejo, cada uno con su autor, contenido, fecha y hora de
  publicación.
- **CA-2. Desempate.** Dado que dos comentarios se publicaron en el mismo segundo, cuando
  abro la app, entonces aparece primero el que se publicó último.
- **CA-3. Estado vacío.** Dado que no existe ningún comentario, cuando abro la app, entonces
  se muestra el mensaje "Aún no hay comentarios. ¡Sé el primero en comentar!" y el contador
  indica "(0)".
- **CA-4. Contador.** Dado que existen 3 comentarios y uno de ellos tiene 2 respuestas,
  cuando abro la app, entonces el contador junto al título indica "(3)": cuenta solo
  comentarios, no respuestas.
- **CA-5. Actualización.** Dado que acabo de publicar un comentario o una respuesta, cuando
  se confirma la publicación, entonces la lista y el contador se actualizan sin que tenga
  que recargar la página.
- **CA-6. Error de carga.** Dado que la lista no se puede obtener, cuando abro la app,
  entonces se muestra el mensaje "No se pudieron cargar los comentarios. Intentá de nuevo
  más tarde." en lugar de la lista.

<!-- corregido por: revisión contra el enunciado. La plantilla tenía 2 criterios para
F-COM-002 (el enunciado pide al menos 3) y no tenía la línea de fuera de alcance. -->

**Fuera de alcance de esta feature:** paginación o "ver más", búsqueda y filtros, cambiar
el orden de la lista, actualización automática cuando otro lector publica (hay que recargar
la página para verlo).

---

### F-RES-001: Responder a un comentario

**Historia de usuario:** Como lector, quiero responder a un comentario existente, para
aportar sobre lo que dijo otra persona sin abrir una conversación nueva.

**Reglas que aplica:** RN-001, RN-002, RN-003, RN-004, RN-005, RN-006, RN-008.

**Criterios de aceptación:**

- **CA-1. Abrir el formulario.** Dado que la lista muestra un comentario, cuando presiono
  "Responder" en ese comentario, entonces aparece debajo de él un formulario con los campos
  autor y contenido y los botones "Publicar respuesta" y "Cancelar".
- **CA-2. Camino feliz.** Dado que el formulario de respuesta está abierto, el autor tiene
  entre 2 y 100 caracteres y el contenido es un texto válido, cuando presiono "Publicar
  respuesta", entonces la respuesta se guarda, aparece debajo de su comentario padre como la
  última de sus respuestas, se muestra el mensaje "Respuesta publicada correctamente" y el
  formulario de respuesta se cierra.
- **CA-3. Contenido inválido.** Dado que el contenido de la respuesta está vacío, tiene solo
  espacios, tiene menos de 3 caracteres o más de 500, cuando presiono "Publicar respuesta",
  entonces se muestra el mismo mensaje que corresponde en F-COM-001 (CA-3, CA-4 o CA-5),
  junto al formulario de respuesta, no se guarda nada y el formulario queda abierto con el
  texto escrito.
- **CA-4. Un solo nivel.** Dado que la lista muestra una respuesta, cuando la miro, entonces
  esa respuesta no tiene botón "Responder": solo los comentarios lo tienen.
- **CA-5. Padre inexistente.** Dado que el comentario padre dejó de existir mientras escribía
  la respuesta, cuando presiono "Publicar respuesta", entonces se muestra el mensaje "El
  comentario al que querés responder no existe" y no se guarda nada.
- **CA-6. Cancelar.** Dado que el formulario de respuesta está abierto con texto escrito,
  cuando presiono "Cancelar", entonces el formulario se cierra, no se guarda nada y el
  comentario queda como estaba.
- **CA-7. Un formulario por comentario.** Dado que el formulario de respuesta de un
  comentario ya está abierto, cuando presiono de nuevo "Responder" en ese mismo comentario,
  entonces el formulario se cierra.

<!-- corregido por: revisión contra la checklist SDD (punto 3). El CA-3 de la plantilla
("no es posible responder directo a esa respuesta") no tenía la forma Dado/cuando/entonces
ni un resultado observable; se reescribió como CA-4. -->

**Fuera de alcance de esta feature:** responder a una respuesta, mencionar a otro autor,
notificar al autor del comentario padre, editar o borrar respuestas, contar las respuestas
en el contador general.

---

## 6. Alcance del proyecto

### DENTRO del alcance (v1)

- Creación de comentarios (F-COM-001).
- Listado de comentarios y sus respuestas, con orden y contador (F-COM-002).
- Respuestas de 1 nivel a un comentario (F-RES-001).
- Validaciones de autor y contenido según RN-001, RN-002 y RN-003.
- Mensajes de éxito y de error en español.
- Una única conversación, de lectura pública.

### FUERA del alcance (futuras versiones)

| Funcionalidad | Gesto | Comentario |
|---|---|---|
| Autenticación de usuarios (login) y roles | Diferir | El autor es solo un nombre visible (RN-003). |
| Edición y borrado de comentarios y respuestas | Diferir | Ver RN-009. Se tratará en F-MOD cuando exista el rol moderador. |
| Moderación y reportes | Diferir | Requiere definir el rol moderador. |
| Likes y reacciones | Restringir | No forman parte del objetivo de la v1. |
| Respuestas a respuestas (anidamiento) | Restringir | Ver RN-004. |
| Texto enriquecido, emojis extendidos, imágenes, enlaces clicables | Restringir | Ver RN-008. |
| Paginación, búsqueda, filtros y cambio de orden | Diferir | El volumen de la v1 no lo justifica. |
| Varias conversaciones o artículos | Diferir | En la v1 hay una única conversación. |
| Actualización en tiempo real | Diferir | Ver F-COM-002, fuera de alcance. |
| Internacionalización | Diferir | La v1 está solo en español. |

---

## 7. Pendientes para Semana 4 (architect.md)

Preguntas de "¿cómo lo implemento?" que surgieron al escribir la spec. No se resuelven acá.

1. **Stack y entorno.** La app actual corre localmente en PHP + MySQL sobre XAMPP. Decidir
   si se mantiene y documentarlo en architect.md (se sacó del contexto, ver sección 1).
2. **Modelo de datos de Respuesta.** ¿Se guarda como entidad propia o como un comentario con
   comentario padre? ¿Qué pasa con las respuestas si en el futuro se borra el padre?
3. **Dónde se valida.** RN-001 a RN-005 tienen que cumplirse aunque alguien publique sin usar
   el formulario. Definir qué se valida en el navegador y qué en el servidor.
4. **Conteo de caracteres.** Garantizar que "á" o un emoji cuenten como 1 carácter en todos
   lados (el código actual cuenta bytes).
5. **Desempate del orden (RN-006).** Definir qué dato garantiza el orden de publicación
   cuando dos publicaciones tienen la misma fecha y hora.
6. **Fecha y hora.** Zona horaria y formato de visualización.
7. **Texto literal (RN-008).** Cómo se evita que el texto ingresado se interprete como código
   y si se conservan los saltos de línea.
8. **Diferencias con el código actual de `main`** que la implementación tendrá que corregir:
   - el contenido acepta de 1 a 1000 caracteres (la spec dice 3 a 500);
   - un nombre con solo espacios puede llegar a guardarse vacío (contradice RN-002);
   - existe una operación de borrado sin ningún control (contradice RN-009 y el alcance);
   - el mensaje de lista vacía y los mensajes de error no coinciden con los de esta spec;
   - todavía no existen las respuestas (F-RES-001).

---

## Anexo — Checklist SDD

- [x] 1. Cada feature tiene un ID único (F-COM-001, F-COM-002, F-RES-001).
- [x] 2. Cada feature tiene una historia de usuario completa (Como / quiero / para).
- [x] 3. Cada feature tiene criterios de aceptación Dado / cuando / entonces, verificables y
  sin "debería".
- [x] 4. Las entidades del dominio están definidas (Comentario, Respuesta y sus relaciones).
- [x] 5. Las reglas de negocio están explícitas (RN-001 a RN-009).
- [x] 6. Hay una sección de alcance con DENTRO y FUERA.
- [x] 7. Un colega puede leer la spec y reproducir el comportamiento sin preguntar.
  *(Entrega individual: se verificó con una relectura propia, buscando dudas del tipo
  "¿y si el texto supera el límite?" o "¿de dónde sale el autor?"; cada una tiene respuesta
  en un criterio o una regla.)*
- [x] 8. La spec no contiene código de implementación.
