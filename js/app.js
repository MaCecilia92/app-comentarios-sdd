const API_URL = 'api/comments.php';

let commentsCache = [];
let currentSort = 'desc';
let currentSearch = '';

document.addEventListener('DOMContentLoaded', () => {
    loadComments();
    setupForm();
    setupCommentActions();
    setupControls();
});

function setupControls() {
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');

    searchInput.addEventListener('input', () => {
        currentSearch = searchInput.value.trim();
        renderComments();
    });

    sortSelect.addEventListener('change', () => {
        currentSort = sortSelect.value;
        renderComments();
    });
}

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

        const likeBtn = e.target.closest('.btn-like');
        if (likeBtn) {
            handleLike(likeBtn);
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

        if (data.success) {
            commentsCache = data.comments;
            renderComments();
        } else {
            container.innerHTML = '<p class="error-message">Error al cargar comentarios</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="error-message">Error al cargar comentarios</p>';
    }
}

function renderComments() {
    const container = document.getElementById('commentsContainer');

    document.getElementById('commentCount').textContent = `(${commentsCache.length})`;

    if (commentsCache.length === 0) {
        container.innerHTML = '<p class="empty-state">No hay comentarios todavía. ¡Sé el primero en comentar!</p>';
        return;
    }

    let tree = buildCommentTree(commentsCache);
    tree = sortTree(tree, currentSort);

    if (currentSearch) {
        tree = filterTree(tree, currentSearch);
    }

    if (tree.length === 0) {
        container.innerHTML = '<p class="empty-state">No se encontraron comentarios que coincidan con la búsqueda.</p>';
        return;
    }

    container.innerHTML = tree.map(comment => renderComment(comment)).join('');
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

    return roots;
}

function sortTree(nodes, direction) {
    const sorted = [...nodes].sort((a, b) => {
        const diff = new Date(a.created_at) - new Date(b.created_at);
        return direction === 'asc' ? diff : -diff;
    });

    sorted.forEach(node => {
        node.replies = sortTree(node.replies, direction);
    });

    return sorted;
}

function filterTree(nodes, term) {
    const lower = term.toLowerCase();

    return nodes
        .map(node => {
            const filteredReplies = filterTree(node.replies, term);
            const selfMatches = node.author.toLowerCase().includes(lower) || node.content.toLowerCase().includes(lower);

            if (selfMatches || filteredReplies.length > 0) {
                return { ...node, replies: filteredReplies };
            }

            return null;
        })
        .filter(Boolean);
}

function renderComment(comment) {
    const repliesHtml = comment.replies.length
        ? `<div class="comment-replies">${comment.replies.map(reply => renderComment(reply)).join('')}</div>`
        : '';

    const liked = getLikedIds().includes(String(comment.id));

    return `
        <div class="comment-card" data-id="${comment.id}">
            <div class="comment-header">
                <span class="comment-author">${escapeHtml(comment.author)}</span>
                <span class="comment-date">${formatDate(comment.created_at)}</span>
            </div>
            <div class="comment-content">${escapeHtml(comment.content)}</div>
            <div class="comment-actions">
                <button type="button" class="btn-like${liked ? ' liked' : ''}" data-id="${comment.id}" ${liked ? 'disabled' : ''}>
                    👍 <span class="like-count">${comment.likes ?? 0}</span>
                </button>
                <button type="button" class="btn-reply">Responder</button>
            </div>
            ${repliesHtml}
        </div>
    `;
}

async function handleLike(button) {
    const id = button.dataset.id;

    if (getLikedIds().includes(String(id))) return;

    button.disabled = true;

    try {
        const response = await fetch(`${API_URL}?action=like&id=${id}`, { method: 'POST' });
        const data = await response.json();

        if (data.success) {
            button.querySelector('.like-count').textContent = data.likes;
            button.classList.add('liked');
            saveLikedId(id);

            const comment = commentsCache.find(c => String(c.id) === String(id));
            if (comment) comment.likes = data.likes;
        } else {
            button.disabled = false;
            showMessage(data.error || 'Error al dar like', 'error');
        }
    } catch (error) {
        button.disabled = false;
        showMessage('Error de conexión', 'error');
    }
}

function getLikedIds() {
    try {
        return JSON.parse(localStorage.getItem('likedComments')) || [];
    } catch (error) {
        return [];
    }
}

function saveLikedId(id) {
    const ids = getLikedIds();
    ids.push(String(id));
    localStorage.setItem('likedComments', JSON.stringify(ids));
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
