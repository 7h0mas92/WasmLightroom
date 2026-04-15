<?php

$pageTitle = e($album['title']);
ob_start();
?>
<div class="album-detail">
    <div class="page-header">
        <div>
            <h1><?= e($album['title']) ?></h1>
            <p class="text-muted">
                par <?= e($album['owner_name']) ?> —
                <span class="badge badge-<?= e($album['visibility']) ?>">
                    <?= e(ucfirst($album['visibility'])) ?>
                </span>
            </p>
            <?php if ($album['description']): ?>
                <p><?= e($album['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="page-actions">
            <a href="/index.php?route=albums" class="btn btn-outline">← Retour</a>
            <?php if ((int)$album['user_id'] === currentUserId()): ?>
                <button class="btn btn-outline" onclick="toggleSharePanel()">👥 Partager</button>
                <form method="POST" action="/index.php?route=album_delete" style="display:inline;"
                    onsubmit="return confirm('Supprimer cet album et toutes ses photos ?');">
                    <input type="hidden" name="album_id" value="<?= (int)$album['id'] ?>">
                    <button type="submit" class="btn btn-danger">🗑 Supprimer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if ((int)$album['user_id'] === currentUserId()): ?>
        <div id="share-panel" class="share-panel" style="display:none;">
            <h3>Partager cet album</h3>
            <div class="share-form">
                <input type="text" id="share-username" placeholder="Nom d'utilisateur..."
                    autocomplete="off" oninput="searchShareUsers(this.value)">
                <div id="share-suggestions" class="suggestions-dropdown"></div>
                <select id="share-permission">
                    <option value="read">Lecture seule</option>
                    <option value="edit">Lecture + Édition</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="shareAlbum(<?= (int)$album['id'] ?>)">
                    Partager
                </button>
            </div>
            <?php if (!empty($shares)): ?>
                <div class="current-shares">
                    <h4>Partagé avec :</h4>
                    <ul>
                        <?php foreach ($shares as $share): ?>
                            <li>
                                <?= e($share['username']) ?> (<?= e($share['permission']) ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    
    <?php if ($canEdit): ?>
        <div class="upload-zone" id="upload-zone">
            <form id="upload-form" enctype="multipart/form-data">
                <input type="hidden" name="album_id" value="<?= (int)$album['id'] ?>">
                <input type="file" id="photo-input" name="photo" accept="image/*" multiple style="display:none;">
                <div class="upload-placeholder" onclick="document.getElementById('photo-input').click()">
                    <p>📤 Cliquez ou glissez des images ici</p>
                    <p class="text-muted">JPG, PNG, WebP, GIF — Max 50 Mo</p>
                </div>
            </form>
            <div id="upload-progress" style="display:none;">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <p id="upload-status"></p>
            </div>
        </div>
    <?php endif; ?>

    
    <div class="photo-grid" id="album-photos">
        <?php if (empty($photos)): ?>
            <div class="empty-state">
                <p>Aucune photo dans cet album.</p>
            </div>
        <?php else: ?>
            <?php foreach ($photos as $photo): ?>
                <div class="photo-card" data-photo-id="<?= (int)$photo['id'] ?>">
                    <img src="/uploads/thumbnails/<?= e($photo['thumbnail_filename']) ?>"
                        alt="<?= e($photo['original_filename']) ?>"
                        loading="lazy">
                    <div class="photo-card-overlay">
                        <a href="/index.php?route=photo_edit&id=<?= (int)$photo['id'] ?>" class="btn btn-sm btn-primary">
                            ✏️ Éditer
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    
    const uploadZone = document.getElementById('upload-zone');
    const photoInput = document.getElementById('photo-input');
    const albumId = <?= (int)$album['id'] ?>;

    if (uploadZone) {
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('drag-over');
        });
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');
            handleFiles(e.dataTransfer.files);
        });
    }

    if (photoInput) {
        photoInput.addEventListener('change', () => handleFiles(photoInput.files));
    }

    async function handleFiles(files) {
        const progress = document.getElementById('upload-progress');
        const status = document.getElementById('upload-status');
        const fill = document.getElementById('progress-fill');
        progress.style.display = 'block';

        for (let i = 0; i < files.length; i++) {
            status.textContent = `Upload ${i + 1} / ${files.length}...`;
            fill.style.width = `${((i) / files.length) * 100}%`;

            const formData = new FormData();
            formData.append('photo', files[i]);
            formData.append('album_id', albumId);

            try {
                const res = await fetch('/index.php?route=photo_upload', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    
                    const grid = document.getElementById('album-photos');
                    const emptyState = grid.querySelector('.empty-state');
                    if (emptyState) emptyState.remove();

                    const card = document.createElement('div');
                    card.className = 'photo-card';
                    card.dataset.photoId = data.photo_id;
                    card.innerHTML = `
                    <img src="${data.thumbnail}" alt="Photo" loading="lazy">
                    <div class="photo-card-overlay">
                        <a href="/index.php?route=photo_edit&id=${data.photo_id}" class="btn btn-sm btn-primary">✏️ Éditer</a>
                    </div>
                `;
                    grid.appendChild(card);
                } else {
                    alert(data.error || 'Erreur upload');
                }
            } catch (err) {
                console.error(err);
                alert('Erreur réseau');
            }
        }

        fill.style.width = '100%';
        status.textContent = 'Upload terminé !';
        setTimeout(() => {
            progress.style.display = 'none';
        }, 2000);
    }

    
    function toggleSharePanel() {
        const panel = document.getElementById('share-panel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }

    async function searchShareUsers(query) {
        const container = document.getElementById('share-suggestions');
        if (query.length < 2) {
            container.innerHTML = '';
            return;
        }

        const res = await fetch(`/index.php?route=api_search_users&q=${encodeURIComponent(query)}`);
        const users = await res.json();
        container.innerHTML = users.map(u =>
            `<div class="suggestion-item" onclick="selectShareUser('${u.username}')">${u.username} (${u.email})</div>`
        ).join('');
    }

    function selectShareUser(username) {
        document.getElementById('share-username').value = username;
        document.getElementById('share-suggestions').innerHTML = '';
    }

    async function shareAlbum(albumId) {
        const username = document.getElementById('share-username').value.trim();
        const permission = document.getElementById('share-permission').value;
        if (!username) return;

        const res = await fetch('/index.php?route=album_share', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                album_id: albumId,
                username,
                permission
            })
        });
        const data = await res.json();

        if (data.success) {
            alert(`Album partagé avec ${data.user} !`);
            location.reload();
        } else {
            alert(data.error || 'Erreur');
        }
    }
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
