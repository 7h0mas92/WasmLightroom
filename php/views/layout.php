<?php

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'WasmLightroom') ?> — WasmLightroom </title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/index.php?route=feed" class="nav-brand"> WasmLightroom</a>
            <div class="nav-links">
                <?php if (currentUserId()): ?>
                    <a href="/index.php?route=feed">Feed</a>
                    <a href="/index.php?route=albums">Mes Albums</a>
                    <span class="nav-user">👤 <?= e(currentUsername()) ?></span>
                    <a href="/index.php?route=logout" class="btn btn-sm btn-outline">Déconnexion</a>
                <?php else: ?>
                    <a href="/index.php?route=login" class="btn btn-sm btn-primary">Connexion</a>
                    <a href="/index.php?route=register" class="btn btn-sm btn-outline">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
            <button class="flash-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>

    <main class="main-content">
        <?= $content ?? '' ?>
    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> WasmLightroom — Projet TROP CARDIO T PAS SYMPA KARLITO</p>
    </footer>

    <script src="/js/app.js" type="module"></script>
</body>

</html>