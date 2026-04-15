<?php


require_once __DIR__ . '/../models/Photo.php';
require_once __DIR__ . '/../helpers/functions.php';

class FeedController
{
    private Photo $photoModel;

    public function __construct()
    {
        $this->photoModel = new Photo();
    }

    public function index(): void
    {
        require __DIR__ . '/../views/feed/index.php';
    }

    public function loadMore(): void
    {
        $limit  = (int) ($_GET['limit'] ?? 10);
        $offset = (int) ($_GET['offset'] ?? 0);
        $userId = currentUserId();

        $limit = min($limit, 50); 

        $photos = $this->photoModel->getFeed($userId, $limit, $offset);
        $total  = $this->photoModel->countFeed($userId);

        jsonResponse([
            'photos'  => $photos,
            'total'   => $total,
            'hasMore' => ($offset + $limit) < $total,
        ]);
    }
}
