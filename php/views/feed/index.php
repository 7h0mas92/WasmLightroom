<?php

$pageTitle = 'Feed';
ob_start();
?>
<div class="feed-container">
    <h1>📰 Fil d'actualité</h1>
    <div id="feed-grid" class="photo-grid"></div>
    <div id="feed-loader" class="loader-container">
        <button id="load-more-btn" class="btn btn-outline" style="display:none;">
            Charger plus
        </button>
        <div id="feed-spinner" class="spinner"></div>
        <p id="feed-empty" class="text-muted" style="display:none;">Aucune photo à afficher pour le moment.</p>
    </div>
</div>


<div id="photo-modal" class="modal" style="display:none;">
    <div class="modal-overlay" onclick="closePhotoModal()"></div>
    <div class="modal-content modal-large">
        <button class="modal-close" onclick="closePhotoModal()">×</button>
        <div class="modal-body">
            <div class="modal-image-container">
                <img id="modal-photo-img" src="" alt="">
            </div>
            <div class="modal-sidebar">
                <div class="modal-photo-info">
                    <h3 id="modal-photo-title"></h3>
                    <p id="modal-photo-author" class="text-muted"></p>
                    <p id="modal-photo-date" class="text-muted"></p>
                </div>
                <div class="modal-actions">
                    <button id="modal-like-btn" class="btn btn-sm btn-outline" onclick="toggleLike()">
                        ❤️ <span id="modal-like-count">0</span>
                    </button>
                    <a id="modal-edit-link" href="#" class="btn btn-sm btn-primary">✏️ Éditer</a>
                </div>
                <div class="modal-comments">
                    <h4>💬 Commentaires</h4>
                    <div id="modal-comments-list"></div>
                    <?php if (currentUserId()): ?>
                        <form id="comment-form" class="comment-form" onsubmit="postComment(event)">
                            <input type="text" id="comment-input" placeholder="Ajouter un commentaire..." required>
                            <button type="submit" class="btn btn-sm btn-primary">Envoyer</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/feed.js" type="module"></script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
