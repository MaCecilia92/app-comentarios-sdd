/*
 * Módulo: Interfaz de conversación
 * Responsabilidad: muestra la lista con su contador, el formulario de nuevo comentario y el
 *   formulario de respuesta de cada comentario. No valida antes de enviar (D-13): envía,
 *   muestra el motivo que devuelve el servidor junto al formulario que lo originó (D-07),
 *   inserta todo texto del lector como texto literal (D-05), muestra la fecha en la hora local
 *   del lector (D-12), no reordena (D-04) y después de publicar vuelve a pedir la conversación
 *   completa (D-06).
 * Decisiones: D-04, D-05, D-06, D-07, D-12, D-13, D-14, D-15
 * Requisitos: RN-006, RN-008, RN-010 · F-COM-001 · F-COM-002 · F-RES-001
 * Fronteras: habla solo con api/conversacion.php. No accede a datos ni decide reglas.
 * Estado: implementado para F-RES-001 (+ mínimo de F-COM-001 y F-COM-002).
 */

'use strict';

const URL_API = 'api/conversacion.php';
const DURACION_EXITO_MS = 3000;

// Los dos únicos textos que viven en la interfaz (excepción de D-13).
const MENSAJE_ERROR_CARGA = 'No se pudieron cargar los comentarios. Intentá de nuevo más tarde.';
const MENSAJE_ERROR_PUBLICACION = 'No se pudo publicar. Intentá de nuevo más tarde.';
const MENSAJE_SIN_COMENTARIOS = 'Aún no hay comentarios. ¡Sé el primero en comentar!';

// Éxito de una respuesta pendiente de mostrar tras volver a cargar la conversación (D-15).
let exitoRespuestaPendiente = null;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('commentForm').addEventListener('submit', enviarComentario);
    document.getElementById('commentsContainer').addEventListener('click', manejarClicEnLista);
    document.getElementById('commentsContainer').addEventListener('submit', enviarRespuesta);
    cargarConversacion();
});

// ---------- Comunicación con la API (D-14) ----------

async function publicar(datos) {
    try {
        const respuesta = await fetch(URL_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });
        const cuerpo = await respuesta.json();
        if (typeof cuerpo.mensaje === 'string') {
            return { exito: cuerpo.exito === true, mensaje: cuerpo.mensaje };
        }
    } catch (error) {
        // Sin respuesta o sin JSON: se informa con el mensaje de la spec (F-COM-001 CA-10).
    }
    return { exito: false, mensaje: MENSAJE_ERROR_PUBLICACION };
}

async function cargarConversacion() {
    const contenedor = document.getElementById('commentsContainer');
    let conversacion;
    try {
        const respuesta = await fetch(URL_API);
        const cuerpo = await respuesta.json();
        if (cuerpo.exito !== true || !Array.isArray(cuerpo.conversacion)) {
            throw new Error('Respuesta sin conversación');
        }
        conversacion = cuerpo.conversacion;
    } catch (error) {
        contenedor.replaceChildren(crearParrafo(MENSAJE_ERROR_CARGA, 'error-message'));
        return;
    }

    // El contador cuenta solo comentarios, no respuestas (F-COM-002 CA-4).
    document.getElementById('commentCount').textContent = `(${conversacion.length})`;

    if (conversacion.length === 0) {
        contenedor.replaceChildren(crearParrafo(MENSAJE_SIN_COMENTARIOS, 'empty-state'));
        return;
    }

    // Se respeta el orden recibido del servidor (D-04).
    contenedor.replaceChildren(...conversacion.map(crearComentario));
    mostrarExitoRespuestaPendiente();
}

// ---------- Nuevo comentario (F-COM-001) ----------

async function enviarComentario(evento) {
    evento.preventDefault();
    const formulario = evento.currentTarget;
    const zonaMensajes = document.getElementById('mensajesComentario');
    zonaMensajes.replaceChildren();

    const resultado = await publicar({
        operacion: 'publicar_comentario',
        autor: formulario.elements.author.value,
        contenido: formulario.elements.content.value
    });

    if (resultado.exito) {
        formulario.reset();
        mostrarMensaje(zonaMensajes, resultado.mensaje, true);
        cargarConversacion();
    } else {
        mostrarMensaje(zonaMensajes, resultado.mensaje, false);
    }
}

// ---------- Respuestas (F-RES-001) ----------

function manejarClicEnLista(evento) {
    const botonResponder = evento.target.closest('.boton-responder');
    if (botonResponder) {
        alternarFormularioRespuesta(botonResponder.closest('.comment-card'));
        return;
    }
    const botonCancelar = evento.target.closest('.boton-cancelar');
    if (botonCancelar) {
        botonCancelar.closest('.formulario-respuesta').remove();
    }
}

function alternarFormularioRespuesta(tarjeta) {
    const zona = tarjeta.querySelector(':scope > .zona-respuesta');
    const abierto = zona.querySelector('.formulario-respuesta');
    if (abierto) {
        abierto.remove();
        return;
    }
    zona.replaceChildren(crearFormularioRespuesta());
    zona.querySelector('input').focus();
}

async function enviarRespuesta(evento) {
    const formulario = evento.target.closest('.formulario-respuesta');
    if (!formulario) {
        return;
    }
    evento.preventDefault();
    const tarjeta = formulario.closest('.comment-card');
    const zonaMensajes = formulario.querySelector('.mensajes-formulario');
    zonaMensajes.replaceChildren();

    const resultado = await publicar({
        operacion: 'publicar_respuesta',
        comentario_id: Number(tarjeta.dataset.id),
        autor: formulario.elements.autor.value,
        contenido: formulario.elements.contenido.value
    });

    if (resultado.exito) {
        exitoRespuestaPendiente = { comentarioId: tarjeta.dataset.id, mensaje: resultado.mensaje };
        cargarConversacion();
    } else {
        mostrarMensaje(zonaMensajes, resultado.mensaje, false);
    }
}

/** D-15: el éxito de una respuesta se ve debajo de su comentario, donde estaba el formulario. */
function mostrarExitoRespuestaPendiente() {
    if (!exitoRespuestaPendiente) {
        return;
    }
    const { comentarioId, mensaje } = exitoRespuestaPendiente;
    exitoRespuestaPendiente = null;
    const tarjeta = document.querySelector(`.comment-card[data-id="${comentarioId}"]`);
    if (tarjeta) {
        mostrarMensaje(tarjeta.querySelector(':scope > .zona-respuesta'), mensaje, true);
    }
}

// ---------- Construcción del DOM (texto siempre literal, D-05) ----------

function crearComentario(comentario) {
    const tarjeta = crearPublicacion(comentario, 'comment-card');
    tarjeta.dataset.id = String(comentario.id);

    const acciones = document.createElement('div');
    acciones.className = 'acciones-comentario';
    const botonResponder = document.createElement('button');
    botonResponder.type = 'button';
    botonResponder.className = 'boton-responder';
    botonResponder.textContent = 'Responder';
    acciones.append(botonResponder);

    const zonaRespuesta = document.createElement('div');
    zonaRespuesta.className = 'zona-respuesta';

    tarjeta.append(acciones, zonaRespuesta);

    if (comentario.respuestas.length > 0) {
        const respuestas = document.createElement('div');
        respuestas.className = 'respuestas';
        // Las respuestas no tienen botón "Responder" (F-RES-001 CA-4).
        respuestas.append(...comentario.respuestas.map(r => crearPublicacion(r, 'respuesta')));
        tarjeta.append(respuestas);
    }
    return tarjeta;
}

function crearPublicacion(publicacion, clase) {
    const elemento = document.createElement('article');
    elemento.className = clase;

    const encabezado = document.createElement('div');
    encabezado.className = 'comment-header';
    encabezado.append(
        crearElementoConTexto('span', publicacion.autor, 'comment-author'),
        crearElementoConTexto('span', formatearFecha(publicacion.fecha_creacion), 'comment-date')
    );

    elemento.append(encabezado, crearElementoConTexto('div', publicacion.contenido, 'comment-content'));
    return elemento;
}

function crearFormularioRespuesta() {
    const formulario = document.createElement('form');
    formulario.className = 'formulario-respuesta';
    formulario.noValidate = true;

    const zonaMensajes = document.createElement('div');
    zonaMensajes.className = 'mensajes-formulario';
    zonaMensajes.setAttribute('aria-live', 'polite');

    const autor = document.createElement('input');
    autor.type = 'text';
    autor.name = 'autor';
    autor.placeholder = 'Tu nombre';

    const contenido = document.createElement('textarea');
    contenido.name = 'contenido';
    contenido.rows = 3;
    contenido.placeholder = 'Escribí tu respuesta...';

    const publicar = document.createElement('button');
    publicar.type = 'submit';
    publicar.className = 'btn-primary';
    publicar.textContent = 'Publicar respuesta';

    const cancelar = document.createElement('button');
    cancelar.type = 'button';
    cancelar.className = 'boton-cancelar';
    cancelar.textContent = 'Cancelar';

    const botones = document.createElement('div');
    botones.className = 'botones-formulario';
    botones.append(publicar, cancelar);

    formulario.append(
        zonaMensajes,
        crearCampo('Nombre:', autor),
        crearCampo('Respuesta:', contenido),
        botones
    );
    return formulario;
}

function crearCampo(textoEtiqueta, control) {
    const grupo = document.createElement('div');
    grupo.className = 'form-group';
    const etiqueta = document.createElement('label');
    etiqueta.append(document.createTextNode(textoEtiqueta), control);
    grupo.append(etiqueta);
    return grupo;
}

function crearElementoConTexto(etiqueta, texto, clase) {
    const elemento = document.createElement(etiqueta);
    elemento.className = clase;
    elemento.textContent = texto;
    return elemento;
}

function crearParrafo(texto, clase) {
    return crearElementoConTexto('p', texto, clase);
}

// ---------- Mensajes (RN-010) ----------

/** Éxito: desaparece a los 3 s. Error: queda hasta el próximo envío o hasta cerrar el formulario. */
function mostrarMensaje(zona, texto, esExito) {
    const mensaje = crearElementoConTexto('div', texto, esExito ? 'success-message' : 'error-message');
    zona.replaceChildren(mensaje);
    if (esExito) {
        setTimeout(() => mensaje.remove(), DURACION_EXITO_MS);
    }
}

/** D-12: la fecha llega en UTC y se muestra en la hora local del lector. */
function formatearFecha(fechaIso) {
    return new Date(fechaIso).toLocaleString('es-AR', { dateStyle: 'medium', timeStyle: 'short' });
}
