<?php


require_once __DIR__ . '/../models/Photo.php';
require_once __DIR__ . '/../models/Album.php';
require_once __DIR__ . '/../models/Recipe.php';
require_once __DIR__ . '/../helpers/functions.php';

class ExportController
{
    private Photo $photoModel;
    private Album $albumModel;
    private Recipe $recipeModel;

    public function __construct()
    {
        $this->photoModel  = new Photo();
        $this->albumModel  = new Album();
        $this->recipeModel = new Recipe();
    }

    
    public function exportRecipe(): void
    {
        $photoId = (int) ($_GET['photo_id'] ?? 0);
        $photo   = $this->photoModel->findById($photoId);

        if (!$photo || !$this->albumModel->canUserAccess((int)$photo['album_id'], currentUserId())) {
            jsonResponse(['error' => 'Accès refusé'], 403);
        }

        $recipe = $this->recipeModel->getByPhoto($photoId);

        header('Content-Disposition: attachment; filename="recipe_' . $photoId . '.json"');
        jsonResponse([
            'photo_id'          => $photoId,
            'original_filename' => $photo['original_filename'],
            'recipe'            => $recipe,
            'exported_at'       => date('c'),
        ]);
    }

    
    public function importRecipe(): void
    {
        requireAuth();

        $input   = json_decode(file_get_contents('php://input'), true);
        $photoId = (int) ($input['photo_id'] ?? 0);
        $steps   = $input['recipe'] ?? [];

        $photo = $this->photoModel->findById($photoId);
        if (!$photo || !$this->albumModel->canUserEdit((int)$photo['album_id'], currentUserId())) {
            jsonResponse(['error' => 'Non autorisé'], 403);
        }

        $success = $this->recipeModel->replaceAll($photoId, currentUserId(), $steps);
        jsonResponse(['success' => $success]);
    }
}
