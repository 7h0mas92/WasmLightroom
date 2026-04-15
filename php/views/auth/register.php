<?php

$pageTitle = 'Inscription';
ob_start();
?>
<div class="auth-container">
    <div class="auth-card">
        <h1>📸 Inscription</h1>
        <form method="POST" action="/index.php?route=register">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>
        <p class="auth-switch">Déjà inscrit ? <a href="/index.php?route=login">Se connecter</a></p>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
