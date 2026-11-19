/*
 * Módulo: Interfaz de conversación
 * Responsabilidad: muestra la lista con su contador, el formulario de nuevo comentario y el
 *   formulario de respuesta de cada comentario. No valida antes de enviar (D-13): envía,
 *   muestra el motivo que devuelve el servidor junto al formulario que lo originó (D-07),
 *   inserta todo texto del lector como texto literal (D-05), muestra la fecha en la hora local
 *   del lector (D-12), no reordena (D-04) y después de publicar vuelve a pedir la conversación
 *   completa (D-06).
 * Decisiones: D-04, D-05, D-06, D-07, D-12, D-13, D-14
 * Requisitos: RN-006, RN-008, RN-010 · F-COM-001 · F-COM-002 · F-RES-001
 * Fronteras: habla solo con api/conversacion.php. No accede a datos ni decide reglas.
 */

const API = 'api/conversacion.php';

// Textos de interfaz del requirements.md. El de error de carga es la única excepción de D-13:
// se usa cuando el servidor no responde y no hay motivo que mostrar.
const MENSAJE_ERROR_CARGA = 'No se pudieron cargar los comentarios. Intentá de nuevo más tarde.'; // F-COM-002 · CA-6
const MENSAJE_SIN_COMENTARIOS = 'Aún no hay comentarios. ¡Sé el primero en comentar!'; // F-COM-002 · CA-3
const DURACION_EXITO_MS = 3000; // RN-010

const contenedor = document.getElementById('commentsContainer');
const contador = document.getElementById('commentCount');
const formularioComentario = document.getElementById('commentForm');
const mensajesComentario = document.getElementById('mensajesComentario');

// ---------- Red (D-14) ----------

// Devuelve el JSON de la respuesta, o null si el servidor no respondió con JSON.
async function pedir(opciones) {
    try {
        const respuesta = await fetch(API, opciones);
        return await respuesta.json();
    } catch (error) {
        return null;
    }
}

function publicar(cuerpo) {
    return pedir({
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(cuerpo),
    });
}

// ---------- Mensajes junto a su formulario (RN-010, D-07) ----------

function limpiarMensaje(zona) {
    clearTimeout(zona.temporizador);
    zona.replaceChildren();
}

function mostrarMensaje(zona, texto, exito) {
    limpiarMensaje(zona);
    const mensaje = document.createElement('p');
    mensaje.className = exito ? 'success-message' : 'error-message';
    mensaje.textContent = texto;
    zona.appendChild(mensaje);
    if (exito) {
        zona.temporizador = setTimeout(() => limpiarMensaje(zona), DURACION_EXITO_MS);
    }
}

// Muestra el motivo del servidor; si no hubo motivo, el de error de carga (D-13).
function mostrarResultado(zona, resultado) {
    const texto = resultado && resultado.mensaje ? resultado.mensaje : MENSAJE_ERROR_CARGA;
    mostrarMensaje(zona, texto, Boolean(resultado && resultado.exito));
}

// ---------- Conversación (F-COM-002) ----------

function formatearFecha(fechaUtc) {
    return new Date(fechaUtc).toLocaleString('es'); // D-12: hora local del lector
}

// Autor, fecha y contenido, siempre como texto literal (D-05, RN-008).
function crearPublicacion(publicacion, clase) {
    const tarjeta = document.createElement('article');
    tarjeta.className = clase;

    const encabezado = document.createElement('div');
    encabezado.className = 'comment-header';
    const autor = document.createElement('span');
    autor.className = 'comment-author';
    autor.textContent = publicacion.autor;
    const fecha = document.createElement('time');
    fecha.className = 'comment-date';
    fecha.dateTime = publicacion.fecha_creacion;
    fecha.textContent = formatearFecha(publicacion.fecha_creacion);
    encabezado.append(autor, fecha);

    const contenido = document.createElement('p');
    contenido.className = 'comment-content';
    contenido.textContent = publicacion.contenido;

    tarjeta.append(encabezado, contenido);
    return tarjeta;
}

function crearComentario(comentario) {
    const tarjeta = crearPublicacion(comentario, 'comment-card');
    tarjeta.dataset.comentarioId = comentario.id;

    // Solo los comentarios tienen "Responder" (F-RES-001 · CA-4).
    const botonResponder = document.createElement('button');
    botonResponder.type = 'button';
    botonResponder.className = 'btn-secondary';
    botonResponder.textContent = 'Responder';

    const mensajesRespuesta = document.createElement('div');
    mensajesRespuesta.className = 'form-messages reply-messages';
    mensajesRespuesta.setAttribute('aria-live', 'polite');

    const zonaFormulario = document.createElement('div');

    botonResponder.addEventListener('click', () => {
        alternarFormularioRespuesta(comentario.id, zonaFormulario, mensajesRespuesta);
    });

    const respuestas = document.createElement('div');
    respuestas.className = 'replies';
    // Llegan ordenadas del servidor (D-04): no se reordenan.
    comentario.respuestas.forEach((respuesta) => {
        respuestas.appendChild(crearPublicacion(respuesta, 'reply-card'));
    });

    tarjeta.append(botonResponder, mensajesRespuesta, zonaFormulario, respuestas);
    return tarjeta;
}

function mostrarConversacion(conversacion) {
    contador.textContent = '(' + conversacion.length + ')'; // cuenta solo comentarios (F-COM-002 · CA-4)
    contenedor.replaceChildren();
    if (conversacion.length === 0) {
        const vacio = document.createElement('p');
        vacio.className = 'empty-state';
        vacio.textContent = MENSAJE_SIN_COMENTARIOS;
        contenedor.appendChild(vacio);
        return;
    }
    conversacion.forEach((comentario) => contenedor.appendChild(crearComentario(comentario)));
}

// D-06: lo que se ve es siempre lo que el servidor acaba de devolver.
async function cargarConversacion() {
    const resultado = await pedir({ method: 'GET' });
    if (!resultado || !resultado.exito || !Array.isArray(resultado.conversacion)) {
        const error = document.createElement('p');
        error.className = 'error-message';
        error.textContent = MENSAJE_ERROR_CARGA;
        contenedor.replaceChildren(error);
        return;
    }
    mostrarConversacion(resultado.conversacion);
}

// ---------- Nuevo comentario (F-COM-001) ----------

formularioComentario.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    limpiarMensaje(mensajesComentario);
    const resultado = await publicar({
        operacion: 'publicar_comentario',
        autor: formularioComentario.elements.author.value,
        contenido: formularioComentario.elements.content.value,
    });
    mostrarResultado(mensajesComentario, resultado);
    if (resultado && resultado.exito) {
        formularioComentario.reset(); // F-COM-001 · CA-1
        await cargarConversacion();
    }
});

// ---------- Respuesta (F-RES-001) ----------

function cerrarFormularioRespuesta(zonaFormulario, mensajesRespuesta) {
    zonaFormulario.replaceChildren();
    limpiarMensaje(mensajesRespuesta); // los errores duran hasta que se cierra el formulario (RN-010)
}

// CA-1 abre el formulario debajo del comentario; CA-7 lo cierra si ya estaba abierto.
function alternarFormularioRespuesta(comentarioId, zonaFormulario, mensajesRespuesta) {
    if (zonaFormulario.firstChild) {
        cerrarFormularioRespuesta(zonaFormulario, mensajesRespuesta);
        return;
    }

    const formulario = document.createElement('form');
    formulario.className = 'reply-form';
    formulario.noValidate = true; // D-13: sin validación nativa del navegador

    const campoAutor = crearCampo('input', 'Nombre:', 'Tu nombre', 'respuesta-autor-' + comentarioId);
    const campoContenido = crearCampo('textarea', 'Respuesta:', 'Escribí tu respuesta...', 'respuesta-contenido-' + comentarioId);

    const botonPublicar = document.createElement('button');
    botonPublicar.type = 'submit';
    botonPublicar.className = 'btn-primary';
    botonPublicar.textContent = 'Publicar respuesta';

    const botonCancelar = document.createElement('button');
    botonCancelar.type = 'button';
    botonCancelar.className = 'btn-secondary';
    botonCancelar.textContent = 'Cancelar';
    // CA-6: se cierra sin guardar nada.
    botonCancelar.addEventListener('click', () => cerrarFormularioRespuesta(zonaFormulario, mensajesRespuesta));

    const acciones = document.createElement('div');
    acciones.className = 'form-actions';
    acciones.append(botonPublicar, botonCancelar);

    formulario.append(campoAutor.grupo, campoContenido.grupo, acciones);

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        limpiarMensaje(mensajesRespuesta);
        const resultado = await publicar({
            operacion: 'publicar_respuesta',
            comentario_id: comentarioId,
            autor: campoAutor.campo.value,
            contenido: campoContenido.campo.value,
        });
        if (!resultado || !resultado.exito) {
            // CA-3, CA-5: el formulario queda abierto con lo escrito.
            mostrarResultado(mensajesRespuesta, resultado);
            return;
        }
        // CA-2: se cierra, se vuelve a pedir la conversación y el mensaje se muestra donde
        // estaba el formulario.
        await cargarConversacion();
        const tarjeta = contenedor.querySelector('[data-comentario-id="' + comentarioId + '"]');
        if (tarjeta) {
            mostrarResultado(tarjeta.querySelector('.reply-messages'), resultado);
        }
    });

    zonaFormulario.appendChild(formulario);
    campoAutor.campo.focus();
}

function crearCampo(etiqueta, textoEtiqueta, ayuda, id) {
    const grupo = document.createElement('div');
    grupo.className = 'form-group';
    const label = document.createElement('label');
    label.htmlFor = id;
    label.textContent = textoEtiqueta;
    const campo = document.createElement(etiqueta);
    campo.id = id;
    campo.placeholder = ayuda;
    if (etiqueta === 'input') {
        campo.type = 'text';
    } else {
        campo.rows = 3;
    }
    grupo.append(label, campo);
    return { grupo, campo };
}

cargarConversacion();
