<?php

$pageTitle = 'Éditeur — ' . e($photo['original_filename']);
ob_start();
?>
<div class="editor-container">
    
    <div class="editor-toolbar">
        <div class="toolbar-left">
            <a href="/index.php?route=album&id=<?= (int)$photo['album_id'] ?>" class="btn btn-sm btn-outline">
                ← Retour
            </a>
            <span class="toolbar-title"><?= e($photo['original_filename']) ?></span>
        </div>
        <div class="toolbar-right">
            <button id="btn-undo" class="btn btn-sm btn-outline" onclick="editorUndo()" disabled>↩ Annuler</button>
            <button id="btn-reset" class="btn btn-sm btn-outline" onclick="editorReset()">🔄 Reset</button>
            <button id="btn-save" class="btn btn-sm btn-primary" onclick="editorSaveRecipe()">💾 Sauvegarder</button>
            <button id="btn-export" class="btn btn-sm btn-outline" onclick="editorExport()">📥 Exporter</button>
        </div>
    </div>

    <div class="editor-body">
        
        <div class="editor-canvas-container">
            <canvas id="editor-canvas" data-photo-id="<?= (int)$photo['id'] ?>"></canvas>
            <canvas id="mask-canvas" style="display:none;"></canvas>
            <div id="editor-loading" class="editor-loading">
                <div class="spinner"></div>
                <p>Chargement du moteur WebAssembly...</p>
            </div>
        </div>

        
        <div class="editor-sidebar">
            
            <div class="editor-panel">
                <h3>🎨 Filtres globaux</h3>
                <div class="filter-group">
                    <label>Luminosité</label>
                    <input type="range" id="filter-brightness" min="-100" max="100" value="0"
                        oninput="editorApplyFilters()">
                    <span id="val-brightness">0</span>
                </div>
                <div class="filter-group">
                    <label>Contraste</label>
                    <input type="range" id="filter-contrast" min="-100" max="100" value="0"
                        oninput="editorApplyFilters()">
                    <span id="val-contrast">0</span>
                </div>
                <div class="filter-group">
                    <label>Sépia</label>
                    <input type="range" id="filter-sepia" min="0" max="100" value="0"
                        oninput="editorApplyFilters()">
                    <span id="val-sepia">0</span>
                </div>
                <div class="filter-group">
                    <label>Niveaux de gris</label>
                    <input type="range" id="filter-grayscale" min="0" max="100" value="0"
                        oninput="editorApplyFilters()">
                    <span id="val-grayscale">0</span>
                </div>
                <div class="filter-group">
                    <label>Inversion</label>
                    <input type="range" id="filter-invert" min="0" max="100" value="0"
                        oninput="editorApplyFilters()">
                    <span id="val-invert">0</span>
                </div>
            </div>

            
            <div class="editor-panel">
                <h3>🎯 Filtre local</h3>
                <div class="filter-group">
                    <label>Mode</label>
                    <select id="local-mode" onchange="editorSetLocalMode(this.value)">
                        <option value="none">Désactivé</option>
                        <option value="radial">Radial (clic)</option>
                        <option value="brush">Pinceau (masque)</option>
                    </select>
                </div>
                <div id="local-options" style="display:none;">
                    <div class="filter-group">
                        <label>Filtre local</label>
                        <select id="local-filter">
                            <option value="grayscale">Niveaux de gris</option>
                            <option value="sepia">Sépia</option>
                            <option value="invert">Inversion</option>
                            <option value="brightness">Luminosité</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Rayon / Taille</label>
                        <input type="range" id="local-radius" min="10" max="300" value="80"
                            oninput="document.getElementById('val-radius').textContent = this.value">
                        <span id="val-radius">80</span>
                    </div>
                    <div class="filter-group">
                        <label>Intensité</label>
                        <input type="range" id="local-intensity" min="0" max="100" value="100"
                            oninput="document.getElementById('val-intensity').textContent = this.value">
                        <span id="val-intensity">100</span>
                    </div>
                    <button id="btn-clear-mask" class="btn btn-sm btn-outline" onclick="editorClearMask()"
                        style="display:none;">
                        Effacer le masque
                    </button>
                </div>
            </div>

            
            <div class="editor-panel">
                <h3>📊 Histogramme</h3>
                <canvas id="histogram-canvas" width="256" height="100"></canvas>
            </div>

            
            <div class="editor-panel">
                <h3>📋 Recette</h3>
                <ul id="recipe-list" class="recipe-list"></ul>
            </div>
        </div>
    </div>
</div>

<script>
    
    window.EDITOR_CONFIG = {
        photoId: <?= (int)$photo['id'] ?>,
        photoUrl: '/index.php?route=photo_serve&id=<?= (int)$photo['id'] ?>',
        recipe: <?= json_encode($recipe ?? []) ?>,
        albumId: <?= (int)$photo['album_id'] ?>
    };
</script>
<script src="/wasm/filters.js"></script>
<script src="/js/editor.js" type="module"></script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
