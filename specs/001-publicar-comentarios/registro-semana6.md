# Registro del Bucle del Agente — App de Comentarios

Herramienta: Otra (Claude Code). Planificación en Plan Mode en una sesión nueva; acción y
evaluación en otra sesión.

## 1. Consigna y criterio de evaluación

**Consigna:** agregá la posibilidad de responder a un comentario (la misma de la Semana 1).

**Criterio de evaluación (EARS):**

> F-RES-001 · CA-2, CA-3, CA-5: CUANDO un lector envía una respuesta válida a un comentario
> existente, EL SISTEMA DEBERÁ guardarla ligada a ese comentario y devolverla como la última de
> sus respuestas; y CUANDO los datos son inválidos o el comentario no existe, EL SISTEMA DEBERÁ
> rechazarla con el mensaje exacto del requirements.md, sin guardar nada. Pasa =
> tests/evaluar_f_res_001.php termina en PASA.

**Condición de corte (declarada antes de iterar):**

> Máximo 3 iteraciones. Si dos iteraciones seguidas fallan en el mismo caso del evaluador,
> corto por "sin progreso" y escalo a revisión humana.

**Preparación antes de la iteración 1:**

- Registré en el `design.md` el contrato entre interfaz y API (D-14) antes de iterar, para que
  la evaluación no se ajustara a lo que implementara el agente.
- Escribí el evaluador externo `tests/evaluar_f_res_001.php` (20 casos: CA-2, CA-3, CA-5,
  bordes de longitud, recorte de espacios, texto literal, saltos de línea, orden y rechazo del
  borrado). Corrido sobre el andamiaje vacío dio NO PASA, como se esperaba.
- El `AGENTS.md` dice que una tarea de respuestas termina cuando el evaluador da PASA y que el
  agente no puede modificar los tests para hacerlos pasar.

## 2. Registro de iteraciones

### Iteración 1

- **01. Planificación:** en Plan Mode, el agente leyó la spec y el `AGENTS.md` y propuso
  implementar F-RES-001 con lo mínimo de F-COM-001 y F-COM-002 sobre los módulos del
  andamiaje (acceso a datos, validación, publicación de comentarios y respuestas, consulta,
  punto de entrada, interfaz y estilos), citando una regla o una D-xx en cada paso. Revisé el
  plan y pedí tres correcciones antes de ejecutar: (1) el plan usaba el mensaje de carga de
  F-COM-002 CA-6 cuando fallaba una publicación, que en ese caso es falso; la spec tenía un
  hueco y lo cerré en la spec (F-COM-001 CA-10 y F-RES-001 CA-8, "No se pudo publicar. Intentá
  de nuevo más tarde.", y la excepción de D-13 ampliada); (2) cuatro decisiones que tomé en
  Plan Mode quedaban solo en el plan, así que se registraron como D-15 (precedencia de
  rechazos, identificador inválido como 404, éxito de una respuesta debajo de su comentario,
  formato del identificador y la fecha); (3) las clases CSS nuevas en español por la regla 9.
- **02. Acción:** primero se commiteó la spec corregida (`requirements.md` v1.3 y `design.md`
  v1.4) y después el código: los seis módulos de `api/` (conexión en UTC, consultas
  preparadas, validación única con mensajes exactos, publicación con 400 y 404, consulta
  ordenada y anidada, punto de entrada con borrado rechazado con 403), `js/conversacion.js`
  (texto siempre literal, fecha en hora local, mensajes junto a su formulario, sin validar
  antes de enviar), estilos y `novalidate` en el formulario. Sin desvíos respecto del plan
  corregido. Sintaxis PHP y JavaScript sin errores.
- **03. Evaluación:** se corrió `tests/evaluar_f_res_001.php` contra la API: 20 casos, 20
  pasan, 0 fallan, código de salida 0. Se cumplen CA-2 (respuesta guardada, mensaje exacto y
  última bajo su comentario), CA-3 (seis rechazos con su mensaje y sin guardar), CA-5 (404 con
  su mensaje), los bordes (3 y 500 caracteres, 500 "á", autor de 2 y 100), RN-003, RN-006,
  RN-008 y D-08. El evaluador no cubre los criterios que solo se ven en pantalla.
- **Resultado:** Pasa.
- **04. Ajuste:** no hubo que corregir el código. Como el criterio automático no prueba la
  interfaz, la iteración 2 evalúa en el navegador los criterios visibles (CA-1, CA-4, CA-6,
  CA-7, ubicación de los mensajes y CA-10/CA-8).

### Iteración 2

- **01. Planificación:** verificar en el navegador los criterios de F-RES-001 y F-COM-001 que
  el evaluador no cubre, con una lista de sí/no: CA-1 (Responder abre el formulario completo),
  CA-2 (el formulario se cierra y el éxito aparece debajo del comentario durante 3 segundos),
  CA-3 (el error aparece junto al formulario de respuesta y lo escrito queda), CA-4 (las
  respuestas no tienen Responder), CA-6 (Cancelar cierra sin guardar), CA-7 (Responder de nuevo
  cierra) y F-COM-001 CA-10 / F-RES-001 CA-8 (mensaje de falla al publicar).
- **02. Acción:** sin cambios de código. Recorrí la lista en
  http://localhost:8080/app-comentarios-sdd/index.html; para la falla de publicación detuve
  MySQL desde el panel de XAMPP, intenté publicar y después lo volví a iniciar.
- **03. Evaluación:** todos los puntos de la lista dieron "sí": el formulario de respuesta se
  abre, cancela y alterna como dicen CA-1, CA-6 y CA-7; las respuestas no tienen Responder
  (CA-4); los mensajes aparecen junto a su formulario y el de éxito desaparece a los 3 segundos
  (CA-2, CA-3, RN-010); con MySQL detenido aparece "No se pudo publicar. Intentá de nuevo más
  tarde." y lo escrito queda (CA-10, CA-8).
- **Resultado:** Pasa.
- **04. Ajuste:** no hace falta. El ciclo cierra por objetivo cumplido, dentro del límite de 3
  iteraciones.

## 3. Cierre y contraste

- **Iteraciones hasta "pasa":** 1 para el criterio automático (el evaluador dio PASA en la
  primera); 2 en total, con la verificación de los criterios de interfaz.
- **Cómo cerró el ciclo:** objetivo cumplido, la evaluación pasó.

**Síntesis final:** la fase con más impacto fue la planificación, entendida como todo lo que
pasó antes de la primera línea de código: el criterio EARS y la condición de corte escritos
antes, el contrato D-14 fijado antes, el evaluador escrito antes (y comprobado en rojo), y la
revisión del plan, que encontró un hueco de la spec (qué ve el lector si falla una
publicación) y cuatro decisiones que habían quedado solo en el plan. Por eso la acción pasó
en el primer intento: no quedaba nada importante para improvisar. La evaluación con un script
externo fue la que permitió decir "pasa" con datos y no con impresión, y también mostró su
límite: no cubre la interfaz, y eso generó la segunda iteración. Frente a la Semana 1, el
pedido fue el mismo, pero allá fue un solo intento sin spec, sin reglas y sin criterio: el
agente agregó likes y búsqueda que nadie pidió, armó el esquema por tres caminos y permitió
anidar respuestas sin límite, y nadie podía decir si "funcionaba". Hoy cada resultado se
contrastó contra un requisito con ID, y el ciclo tenía un freno definido de antemano.
