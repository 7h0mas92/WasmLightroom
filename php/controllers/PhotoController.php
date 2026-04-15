<?php


require_once __DIR__ . '/../models/Photo.php';
require_once __DIR__ . '/../models/Album.php';
require_once __DIR__ . '/../models/Recipe.php';
require_once __DIR__ . '/../models/Like.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../helpers/functions.php';

class PhotoController
{
    private Photo $photoModel;
    private Album $albumModel;
    private Recipe $recipeModel;
    private Like $likeModel;
    private Comment $commentModel;

    public function __construct()
    {
        $this->photoModel   = new Photo();
        $this->albumModel   = new Album();
        $this->recipeModel  = new Recipe();
        $this->likeModel    = new Like();
        $this->commentModel = new Comment();
    }

    public function upload(): void
    {
        requireAuth();

        $albumId = (int) ($_POST['album_id'] ?? 0);
        $userId  = currentUserId();

        if (!$this->albumModel->canUserEdit($albumId, $userId)) {
            jsonResponse(['error' => 'Non autorisé'], 403);
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['error' => 'Erreur lors de l\'upload'], 400);
        }

        $file = $_FILES['photo'];

        
        if (!isValidImageMime($file['tmp_name'])) {
            jsonResponse(['error' => 'Format de fichier non autorisé. Utilisez JPG, PNG, WebP ou GIF.'], 400);
        }

        
        if ($file['size'] > 50 * 1024 * 1024) {
            jsonResponse(['error' => 'Fichier trop volumineux (max 50 Mo).'], 400);
        }

        $storedName = generateSecureFilename($file['name']);
        $uploadDir  = __DIR__ . '/../uploads/originals/';
        $thumbDir   = __DIR__ . '/../uploads/thumbnails/';

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

        $destPath = $uploadDir . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            jsonResponse(['error' => 'Impossible de sauvegarder le fichier.'], 500);
        }

        
        $imgInfo = getimagesize($destPath);
        $width   = $imgInfo[0] ?? null;
        $height  = $imgInfo[1] ?? null;

        
        $thumbName = 'thumb_' . $storedName;
        createThumbnail($destPath, $thumbDir . $thumbName, 400);

        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($destPath);

        $photoId = $this->photoModel->create([
            'album_id'           => $albumId,
            'user_id'            => $userId,
            'original_filename'  => $file['name'],
            'stored_filename'    => $storedName,
            'mime_type'          => $mime,
            'file_size'          => $file['size'],
            'width'              => $width,
            'height'             => $height,
            'thumbnail_filename' => $thumbName,
        ]);

        jsonResponse([
            'success'  => true,
            'photo_id' => $photoId,
            'thumbnail' => '/uploads/thumbnails/' . $thumbName,
        ]);
    }

    public function edit(): void
    {
        requireAuth();
        $photoId = (int) ($_GET['id'] ?? 0);
        $photo   = $this->photoModel->findById($photoId);

        if (!$photo) {
            setFlash('error', 'Photo introuvable.');
            redirect('/index.php?route=feed');
        }

        if (!$this->albumModel->canUserAccess((int)$photo['album_id'], currentUserId())) {
            setFlash('error', 'Accès refusé.');
            redirect('/index.php?route=feed');
        }

        $recipe = $this->recipeModel->getByPhoto($photoId);
        require __DIR__ . '/../views/editor/index.php';
    }

    public function saveRecipe(): void
    {
        requireAuth();

        $input   = json_decode(file_get_contents('php://input'), true);
        $photoId = (int) ($input['photo_id'] ?? 0);
        $steps   = $input['steps'] ?? [];
        $userId  = currentUserId();

        $photo = $this->photoModel->findById($photoId);
        if (!$photo || !$this->albumModel->canUserEdit((int)$photo['album_id'], $userId)) {
            jsonResponse(['error' => 'Non autorisé'], 403);
        }

        $success = $this->recipeModel->replaceAll($photoId, $userId, $steps);
        jsonResponse(['success' => $success]);
    }

    public function getRecipe(): void
    {
        $photoId = (int) ($_GET['photo_id'] ?? 0);
        $recipe  = $this->recipeModel->getByPhoto($photoId);
        jsonResponse(['steps' => $recipe]);
    }

    public function like(): void
    {
        requireAuth();
        $input   = json_decode(file_get_contents('php://input'), true);
        $photoId = (int) ($input['photo_id'] ?? 0);
        $result  = $this->likeModel->toggle($photoId, currentUserId());
        $result['success'] = true;
        jsonResponse($result);
    }

    public function comment(): void
    {
        requireAuth();
        $input   = json_decode(file_get_contents('php://input'), true);
        $photoId = (int) ($input['photo_id'] ?? 0);
        $content = trim($input['content'] ?? '');

        if (empty($content)) {
            jsonResponse(['error' => 'Commentaire vide'], 400);
        }

        $this->commentModel->create($photoId, currentUserId(), $content);
        $comments = $this->commentModel->findByPhoto($photoId);
        jsonResponse(['success' => true, 'comments' => $comments]);
    }

    public function getComments(): void
    {
        $photoId  = (int) ($_GET['photo_id'] ?? 0);
        $comments = $this->commentModel->findByPhoto($photoId);
        jsonResponse(['comments' => $comments]);
    }

    public function delete(): void
    {
        requireAuth();
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        $photo   = $this->photoModel->findById($photoId);

        if (!$photo || (int)$photo['user_id'] !== currentUserId()) {
            jsonResponse(['error' => 'Non autorisé'], 403);
        }

        
        $origPath  = __DIR__ . '/../uploads/originals/' . $photo['stored_filename'];
        $thumbPath = __DIR__ . '/../uploads/thumbnails/' . $photo['thumbnail_filename'];
        if (file_exists($origPath)) unlink($origPath);
        if (file_exists($thumbPath)) unlink($thumbPath);

        $this->photoModel->delete($photoId);
        jsonResponse(['success' => true]);
    }

    public function serve(): void
    {
        $photoId = (int) ($_GET['id'] ?? 0);
        $photo   = $this->photoModel->findById($photoId);

        if (!$photo) {
            http_response_code(404);
            exit;
        }

        if (!$this->albumModel->canUserAccess((int)$photo['album_id'], currentUserId())) {
            http_response_code(403);
            exit;
        }

        $path = __DIR__ . '/../uploads/originals/' . $photo['stored_filename'];
        if (!file_exists($path)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $photo['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=86400');
        readfile($path);
        exit;
    }
}
