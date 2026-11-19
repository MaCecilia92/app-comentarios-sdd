# Registro de la Semana 6 — App de Comentarios

### Desafío extra: iteración en modo ReAct

En una sesión de Claude Code que cargó las reglas desde el repo, le pedí implementar F-RES-001 sin Plan Mode, en un ciclo acción → evaluador → decisión, con un corte fijo.

**Pedido (textual):**

> No uses Plan Mode ni me muestres un plan antes: trabajá en modo ReAct, paso a paso.
> Leé specs/001-publicar-comentarios/requirements.md y design.md, y respetá el AGENTS.md de la raíz y el de api/.
> Objetivo: agregar la posibilidad de responder a un comentario (F-RES-001), con lo mínimo de F-COM-001 y F-COM-002 para que funcione de punta a punta. El contrato entre interfaz y API es el de design.md D-14.
> Ciclo: hacé un cambio, corré C:\xampp\php\php.exe tests\evaluar_f_res_001.php, mirá el resultado y decidí el siguiente paso. No modifiques el evaluador.
> Corte: como máximo 3 corridas del evaluador. Si a la tercera no da PASA, o si dos corridas seguidas fallan en el mismo caso, frená y reportá.
> Al terminar, listá cada corrida con qué cambiaste antes, cuántos casos pasaron y cuáles fallaron. No hagas commit.

**Corridas:**

| # | Cambio previo | Pasan | Fallan |
|---|---|---|---|
| 1 | Lógica de los seis módulos de `api/` según D-14 (validación, acceso a datos, publicación de comentarios y de respuestas, consulta y punto de entrada) | 20 / 20 | — |
| 2 | Interfaz: `js/conversacion.js`, `novalidate` en `index.html` y estilos de respuestas. La API no cambió | 20 / 20 | — |

**Resultado: PASA en 2 de 3 corridas, sin tocar el evaluador.** Observaciones:

- Antes de gastar corridas, el agente verificó el entorno (alias de Apache, esquema de la base) y la sintaxis de los archivos.
- Probó aparte, con pedidos directos a la API, los mensajes de comentario de F-COM-001 · CA-3 a CA-6, que el evaluador no cubre.
- No inventó textos: el 405 y el 500 responden sin `mensaje` porque el `requirements.md` no tiene un texto para esos casos, y lo señaló.
- Dejó como dudas, sin resolverlas, tres huecos de la spec: el texto del 405 y del 500, el orden 400 vs. 404 cuando una respuesta tiene datos inválidos y además su comentario no existe, y la pérdida de los formularios de respuesta abiertos al recargar la lista (D-06).
- Respetó el corte: no usó la tercera corrida porque el criterio ya estaba cumplido.

**Verificación manual en el navegador (no la cubre el evaluador):** la hice yo y se cumplen todos los criterios.

- [x] CA-1 Cada comentario tiene "Responder" y abre un formulario con autor, contenido, "Publicar respuesta" y "Cancelar".
- [x] CA-2 Tras publicar se ve "Respuesta publicada correctamente" y el formulario se cierra.
- [x] CA-3 El mensaje de error aparece junto al formulario de respuesta y lo escrito queda.
- [x] CA-4 Las respuestas no tienen botón "Responder".
- [x] CA-6 "Cancelar" cierra el formulario sin guardar.
- [x] CA-7 Presionar de nuevo "Responder" cierra el formulario.

**Contraste con el Plan Mode de la Semana 5:** [completar: qué ganaste y qué perdiste sin revisar un plan antes; qué hizo de freno en lugar de la revisión del plan, por ejemplo el evaluador y el corte].
