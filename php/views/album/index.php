<?php

$pageTitle = 'Mes Albums';
ob_start();
?>
<div class="albums-container">
    <div class="page-header">
        <h1>📁 Mes Albums</h1>
        <a href="/index.php?route=album_create" class="btn btn-primary">+ Nouvel Album</a>
    </div>

    <?php if (!empty($myAlbums)): ?>
        <h2>Mes albums</h2>
        <div class="album-grid">
            <?php foreach ($myAlbums as $album): ?>
                <a href="/index.php?route=album&id=<?= (int)$album['id'] ?>" class="album-card">
                    <div class="album-card-icon">
                        <?php
                        $icon = match ($album['visibility']) {
                            'public'  => '🌍',
                            'shared'  => '👥',
                            default   => '🔒',
                        };
                        echo $icon;
                        ?>
                    </div>
                    <h3><?= e($album['title']) ?></h3>
                    <p class="text-muted"><?= (int)$album['photo_count'] ?> photo(s)</p>
                    <span class="badge badge-<?= e($album['visibility']) ?>">
                        <?= e(ucfirst($album['visibility'])) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>Vous n'avez pas encore d'album.</p>
            <a href="/index.php?route=album_create" class="btn btn-primary">Créer mon premier album</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($sharedAlbums)): ?>
        <h2>Albums partagés avec moi</h2>
        <div class="album-grid">
            <?php foreach ($sharedAlbums as $album): ?>
                <a href="/index.php?route=album&id=<?= (int)$album['id'] ?>" class="album-card album-shared">
                    <div class="album-card-icon">👥</div>
                    <h3><?= e($album['title']) ?></h3>
                    <p class="text-muted">par <?= e($album['owner_name']) ?></p>
                    <p class="text-muted"><?= (int)$album['photo_count'] ?> photo(s)</p>
                    <span class="badge badge-perm"><?= e(ucfirst($album['permission'])) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
