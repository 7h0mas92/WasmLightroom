<?php


require_once __DIR__ . '/../models/Album.php';
require_once __DIR__ . '/../models/Photo.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/functions.php';

class AlbumController
{
    private Album $albumModel;
    private Photo $photoModel;
    private User $userModel;

    public function __construct()
    {
        $this->albumModel = new Album();
        $this->photoModel = new Photo();
        $this->userModel  = new User();
    }

    public function index(): void
    {
        requireAuth();
        $userId = currentUserId();
        $myAlbums     = $this->albumModel->findByUser($userId);
        $sharedAlbums = $this->albumModel->findSharedWithUser($userId);
        require __DIR__ . '/../views/album/index.php';
    }

    public function show(): void
    {
        $albumId = (int) ($_GET['id'] ?? 0);
        $userId  = currentUserId();

        if (!$this->albumModel->canUserAccess($albumId, $userId)) {
            setFlash('error', 'Accès refusé à cet album.');
            redirect('/index.php?route=albums');
        }

        $album  = $this->albumModel->findById($albumId);
        $photos = $this->photoModel->findByAlbum($albumId);
        $shares = $this->albumModel->getShares($albumId);
        $canEdit = $this->albumModel->canUserEdit($albumId, $userId);

        require __DIR__ . '/../views/album/show.php';
    }

    public function create(): void
    {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $visibility  = $_POST['visibility'] ?? 'private';

            if (empty($title)) {
                setFlash('error', 'Le titre est obligatoire.');
                redirect('/index.php?route=album_create');
            }

            if (!in_array($visibility, ['private', 'public', 'shared'])) {
                $visibility = 'private';
            }

            $albumId = $this->albumModel->create(currentUserId(), $title, $description, $visibility);
            setFlash('success', 'Album créé !');
            redirect('/index.php?route=album&id=' . $albumId);
        }

        require __DIR__ . '/../views/album/create.php';
    }

    public function delete(): void
    {
        requireAuth();
        $albumId = (int) ($_POST['album_id'] ?? 0);
        $album = $this->albumModel->findById($albumId);

        if (!$album || (int)$album['user_id'] !== currentUserId()) {
            setFlash('error', 'Action non autorisée.');
            redirect('/index.php?route=albums');
        }

        $this->albumModel->delete($albumId);
        setFlash('success', 'Album supprimé.');
        redirect('/index.php?route=albums');
    }

    public function share(): void
    {
        requireAuth();
        $albumId  = (int) ($_POST['album_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $perm     = $_POST['permission'] ?? 'read';

        $album = $this->albumModel->findById($albumId);
        if (!$album || (int)$album['user_id'] !== currentUserId()) {
            jsonResponse(['error' => 'Non autorisé'], 403);
        }

        $targetUser = $this->userModel->findByUsername($username);
        if (!$targetUser) {
            jsonResponse(['error' => 'Utilisateur introuvable'], 404);
        }

        $this->albumModel->shareWith($albumId, (int)$targetUser['id'], $perm);
        jsonResponse(['success' => true, 'user' => $targetUser['username']]);
    }

    public function searchUsers(): void
    {
        requireAuth();
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            jsonResponse([]);
        }
        $users = $this->userModel->searchByUsername($query, currentUserId());
        jsonResponse($users);
    }
}
