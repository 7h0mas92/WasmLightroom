<?php


require_once __DIR__ . '/../config/database.php';

class Like
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function toggle(int $photoId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM likes WHERE photo_id = :pid AND user_id = :uid'
        );
        $stmt->execute([':pid' => $photoId, ':uid' => $userId]);

        if ($stmt->fetch()) {
            $del = $this->db->prepare(
                'DELETE FROM likes WHERE photo_id = :pid AND user_id = :uid'
            );
            $del->execute([':pid' => $photoId, ':uid' => $userId]);
            $liked = false;
        } else {
            $ins = $this->db->prepare(
                'INSERT INTO likes (photo_id, user_id) VALUES (:pid, :uid)'
            );
            $ins->execute([':pid' => $photoId, ':uid' => $userId]);
            $liked = true;
        }

        $count = $this->db->prepare(
            'SELECT COUNT(*) as c FROM likes WHERE photo_id = :pid'
        );
        $count->execute([':pid' => $photoId]);

        return [
            'liked'      => $liked,
            'like_count' => (int) $count->fetch()['c'],
        ];
    }
}
