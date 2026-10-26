const API_URL = 'api/comments.php';

document.addEventListener('DOMContentLoaded', () => {
    loadComments();
    setupForm();
    setupCommentActions();
});

function setupForm() {
    const form = document.getElementById('commentForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const author = document.getElementById('author').value.trim();
        const content = document.getElementById('content').value.trim();
        
        if (!author || !content) {
            showMessage('Por favor completá todos los campos', 'error');
            return;
        }
        
        await submitComment({ author, content });
        document.getElementById('commentForm').reset();
    });
}

function setupCommentActions() {
    const container = document.getElementById('commentsContainer');

    container.addEventListener('click', (e) => {
        const replyBtn = e.target.closest('.btn-reply');
        const cancelBtn = e.target.closest('.btn-cancel-reply');

        if (replyBtn) {
            toggleReplyForm(replyBtn.closest('.comment-card'));
        }

        if (cancelBtn) {
            const card = cancelBtn.closest('.comment-card');
            const form = card.querySelector('.reply-form');
            if (form) form.remove();
        }
    });

    container.addEventListener('submit', async (e) => {
        const replyForm = e.target.closest('.reply-form');
        if (!replyForm) return;

        e.preventDefault();

        const card = replyForm.closest('.comment-card');
        const author = replyForm.querySelector('[name="reply-author"]').value.trim();
        const content = replyForm.querySelector('[name="reply-content"]').value.trim();
        const parentId = Number(card.dataset.id);

        if (!author || !content) {
            showMessage('Por favor completá todos los campos', 'error');
            return;
        }

        await submitComment({ author, content, parent_id: parentId });
    });
}

async function submitComment(payload) {
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            const isReply = Boolean(payload.parent_id);
            showMessage(isReply ? 'Respuesta publicada correctamente' : 'Comentario publicado correctamente', 'success');
            loadComments();
        } else {
            showMessage(data.error || 'Error al publicar', 'error');
        }
    } catch (error) {
        showMessage('Error de conexión', 'error');
    }
}

async function loadComments() {
    const container = document.getElementById('commentsContainer');
    
    try {
        const response = await fetch(API_URL);
        const data = await response.json();
        
        if (data.success && data.comments.length > 0) {
            document.getElementById('commentCount').textContent = `(${data.comments.length})`;
            const tree = buildCommentTree(data.comments);
            container.innerHTML = tree.map(comment => renderComment(comment)).join('');
        } else {
            document.getElementById('commentCount').textContent = '(0)';
            container.innerHTML = '<p class="empty-state">No hay comentarios todavía. ¡Sé el primero en comentar!</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="error-message">Error al cargar comentarios</p>';
    }
}

function buildCommentTree(comments) {
    const byId = {};
    comments.forEach(comment => {
        byId[comment.id] = { ...comment, replies: [] };
    });

    const roots = [];
    comments.forEach(comment => {
        const node = byId[comment.id];
        const parentId = comment.parent_id;

        if (parentId && byId[parentId]) {
            byId[parentId].replies.push(node);
        } else {
            roots.push(node);
        }
    });

    return roots.reverse();
}

function renderComment(comment) {
    const repliesHtml = comment.replies.length
        ? `<div class="comment-replies">${comment.replies.map(reply => renderComment(reply)).join('')}</div>`
        : '';

    return `
        <div class="comment-card" data-id="${comment.id}">
            <div class="comment-header">
                <span class="comment-author">${escapeHtml(comment.author)}</span>
                <span class="comment-date">${formatDate(comment.created_at)}</span>
            </div>
            <div class="comment-content">${escapeHtml(comment.content)}</div>
            <div class="comment-actions">
                <button type="button" class="btn-reply">Responder</button>
            </div>
            ${repliesHtml}
        </div>
    `;
}

function toggleReplyForm(card) {
    const existing = card.querySelector(':scope > .reply-form');
    if (existing) {
        existing.remove();
        return;
    }

    const form = document.createElement('form');
    form.className = 'reply-form';
    form.innerHTML = `
        <div class="form-group">
            <label>Nombre:</label>
            <input type="text" name="reply-author" required placeholder="Tu nombre">
        </div>
        <div class="form-group">
            <label>Respuesta:</label>
            <textarea name="reply-content" required placeholder="Escribí tu respuesta..." rows="3"></textarea>
        </div>
        <div class="reply-form-actions">
            <button type="submit" class="btn-primary">Publicar respuesta</button>
            <button type="button" class="btn-cancel-reply">Cancelar</button>
        </div>
    `;

    const actions = card.querySelector(':scope > .comment-actions');
    actions.insertAdjacentElement('afterend', form);
    form.querySelector('[name="reply-author"]').focus();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showMessage(text, type) {
    const existing = document.querySelector('.error-message, .success-message');
    if (existing) existing.remove();
    
    const msg = document.createElement('div');
    msg.className = type === 'error' ? 'error-message' : 'success-message';
    msg.textContent = text;
    
    const form = document.querySelector('.comment-form');
    form.insertBefore(msg, form.firstChild);
    
    setTimeout(() => msg.remove(), 3000);
}
