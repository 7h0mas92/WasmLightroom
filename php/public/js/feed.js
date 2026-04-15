


'use strict';

let feedOffset = 0;
const FEED_LIMIT = 10;
let feedLoading = false;
let feedHasMore = true;
let currentModalPhotoId = null;

document.addEventListener('DOMContentLoaded', () => {
    loadFeed();

    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadFeed);
    }

    
    window.addEventListener('scroll', () => {
        if (feedLoading || !feedHasMore) return;
        const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
        if (scrollTop + clientHeight >= scrollHeight - 200) {
            loadFeed();
        }
    });
});

async function loadFeed() {
    if (feedLoading || !feedHasMore) return;
    feedLoading = true;

    const spinner = document.getElementById('feed-spinner');
    const loadMoreBtn = document.getElementById('load-more-btn');
    const emptyMsg = document.getElementById('feed-empty');

    if (spinner) spinner.style.display = 'block';
    if (loadMoreBtn) loadMoreBtn.style.display = 'none';

    try {
        const res = await fetch(
            `/index.php?route=api_feed&limit=${FEED_LIMIT}&offset=${feedOffset}`
        );
        const data = await res.json();

        const grid = document.getElementById('feed-grid');

        if (data.photos.length === 0 && feedOffset === 0) {
            if (emptyMsg) emptyMsg.style.display = 'block';
        } else {
            data.photos.forEach(photo => {
                grid.appendChild(createFeedCard(photo));
            });
        }

        feedOffset += data.photos.length;
        feedHasMore = data.hasMore;

        if (feedHasMore && loadMoreBtn) {
            loadMoreBtn.style.display = 'inline-block';
        }
    } catch (err) {
        console.error('Erreur chargement feed:', err);
    } finally {
        feedLoading = false;
        if (spinner) spinner.style.display = 'none';
    }
}

function createFeedCard(photo) {
    const card = document.createElement('div');
    card.className = 'feed-card';
    card.dataset.photoId = photo.id;

    const thumbUrl = `/uploads/thumbnails/${escapeHtml(photo.thumbnail_filename)}`;
    const liked = photo.user_liked > 0;

    card.innerHTML = `
        <div class="feed-card-image" onclick="openPhotoModal(${photo.id})">
            <img src="${thumbUrl}" alt="${escapeHtml(photo.original_filename)}" loading="lazy">
        </div>
        <div class="feed-card-info">
            <div class="feed-card-meta">
                <span class="feed-author">👤 ${escapeHtml(photo.author_name)}</span>
                <span class="feed-album">📁 ${escapeHtml(photo.album_title)}</span>
            </div>
            <div class="feed-card-actions">
                <span class="feed-likes ${liked ? 'liked' : ''}">
                    ❤️ ${photo.like_count || 0}
                </span>
                <span class="feed-comments">💬 ${photo.comment_count || 0}</span>
                <a href="/index.php?route=photo_edit&id=${photo.id}" class="btn btn-xs btn-outline">
                    ✏️ Éditer
                </a>
            </div>
        </div>
    `;
    return card;
}



async function openPhotoModal(photoId) {
    currentModalPhotoId = photoId;
    const modal = document.getElementById('photo-modal');

    const imgEl = document.getElementById('modal-photo-img');
    imgEl.src = `/index.php?route=photo_serve&id=${photoId}`;

    const editLink = document.getElementById('modal-edit-link');
    editLink.href = `/index.php?route=photo_edit&id=${photoId}`;

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    
    loadComments(photoId);
}

function closePhotoModal() {
    const modal = document.getElementById('photo-modal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    currentModalPhotoId = null;
}



async function toggleLike() {
    if (!currentModalPhotoId) return;
    try {
        const res = await fetch('/index.php?route=api_like', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ photo_id: currentModalPhotoId })
        });
        const data = await res.json();
        if (data.success) {
            const btn = document.getElementById('modal-like-btn');
            const count = document.getElementById('modal-like-count');
            if (count) count.textContent = data.like_count;
            if (btn) btn.classList.toggle('liked', data.liked);
        }
    } catch (err) {
        console.error('Erreur like:', err);
    }
}

async function loadComments(photoId) {
    try {
        const res = await fetch(`/index.php?route=api_comments&photo_id=${photoId}`);
        const data = await res.json();
        const list = document.getElementById('modal-comments-list');
        list.innerHTML = '';

        if (data.comments && data.comments.length > 0) {
            data.comments.forEach(c => {
                const div = document.createElement('div');
                div.className = 'comment-item';
                div.innerHTML = `
                    <strong>${escapeHtml(c.username)}</strong>
                    <span class="comment-date">${c.created_at}</span>
                    <p>${escapeHtml(c.content)}</p>
                `;
                list.appendChild(div);
            });
        } else {
            list.innerHTML = '<p class="text-muted">Aucun commentaire.</p>';
        }
    } catch (err) {
        console.error('Erreur chargement commentaires:', err);
    }
}

async function postComment(event) {
    event.preventDefault();
    if (!currentModalPhotoId) return;

    const input = document.getElementById('comment-input');
    const content = input.value.trim();
    if (!content) return;

    try {
        const res = await fetch('/index.php?route=api_comment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                photo_id: currentModalPhotoId,
                content: content
            })
        });
        const data = await res.json();
        if (data.success) {
            input.value = '';
            loadComments(currentModalPhotoId);
        } else {
            alert(data.error || 'Erreur');
        }
    } catch (err) {
        console.error('Erreur post commentaire:', err);
    }
}


function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}


window.openPhotoModal = openPhotoModal;
window.closePhotoModal = closePhotoModal;
window.toggleLike = toggleLike;
window.postComment = postComment;
