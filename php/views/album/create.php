<?php

$pageTitle = 'Nouvel Album';
ob_start();
?>
<div class="form-container">
    <h1>📁 Créer un Album</h1>
    <form method="POST" action="/index.php?route=album_create">
        <div class="form-group">
            <label for="title">Titre *</label>
            <input type="text" id="title" name="title" required maxlength="255" autofocus>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" maxlength="1000"></textarea>
        </div>
        <div class="form-group">
            <label for="visibility">Visibilité</label>
            <select id="visibility" name="visibility">
                <option value="private">🔒 Privé</option>
                <option value="public">🌍 Public</option>
                <option value="shared">👥 Partagé</option>
            </select>
        </div>
        <div class="form-actions">
            <a href="/index.php?route=albums" class="btn btn-outline">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer l'album</button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
