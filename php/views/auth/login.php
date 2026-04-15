<?php

$pageTitle = 'Connexion';
ob_start();
?>
<div class="auth-container">
    <div class="auth-card">
        <h1>📸 Connexion</h1>
        <form method="POST" action="/index.php?route=login">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
        <p class="auth-switch">Pas encore inscrit ? <a href="/index.php?route=register">S'inscrire</a></p>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
