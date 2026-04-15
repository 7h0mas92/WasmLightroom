# WasmLightroom

WasmLightroom est une application web de gestion et de retouche de photos. Elle intègre un éditeur d'images performant s'appuyant sur **WebAssembly (WASM)** pour appliquer des filtres photographiques en temps réel directement dans le navigateur.

## 🚀 Fonctionnalités
- **Authentification** : Inscription, connexion et gestion utilisateur.
- **Flux Social (Feed)** : Découverte de photos, likes et commentaires.
- **Éditeur WASM** : Application de filtres photo avec accélération matérielle (code C++ compilé via Emscripten).
- **Gestion d'Albums** : Création et organisation méticuleuse de vos photos.
- **Recettes de filtres** : Sauvegarde de paramètres de retouche ("Recipes").
- **Exportation** : Sauvegarde et téléchargement des images retouchées.

## 🛠️ Technologies
- **Backend** : PHP (Architecture MVC personnalisée)
- **Frontend** : HTML, CSS, JavaScript (avec Web Workers)
- **Traitement d'image** : C++ compilé en WebAssembly (.wasm)
- **Infrastructure** : Docker, Docker Compose, Nginx, Base de données SQL

## 📂 Structure du projet
- `docker/` : Configuration de l'environnement serveur (Nginx).
- `php/` : Code source métier (Controlleurs, Modèles, Vues, Helpers).
  - `php/public/` : Point d'entrée de l'application web (`index.php`) et dossiers statiques (`css/`, `js/`, `wasm/`).
  - `php/uploads/` : Dossier de stockage sécurisé des images originales et miniatures.
- `wasm/` : Code source C++ natif de l'éditeur d'images et scripts de compilation Dockerisés.
- `compose.yml` : Orchestration des conteneurs.
- `dump.sql` : Structure initiale de la base de données.

## ⚙️ Démarrage rapide

1. **Lancement de l'environnement Docker :**
   ```bash
   docker compose up -d
   ```
2. **Base de données :**
   La base de données s'initialise généralement avec le fichier `dump.sql`. Configurez les accès dans `php/config/database.php`.
3. **Compilation de WebAssembly (Si modification du C++) :**
   Utilisez le conteneur prévu dans le dossier `wasm/` pour compiler `filters.cpp` vers `php/public/wasm/`.

---


