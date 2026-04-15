<?php


require_once __DIR__ . '/../config/database.php';

class Comment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $photoId, int $userId, string $content): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comments (photo_id, user_id, content) VALUES (:pid, :uid, :content)'
        );
        $stmt->execute([
            ':pid'     => $photoId,
            ':uid'     => $userId,
            ':content' => $content,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findByPhoto(int $photoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.username
             FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.photo_id = :pid
             ORDER BY c.created_at ASC'
        );
        $stmt->execute([':pid' => $photoId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM comments WHERE id = :id AND user_id = :uid'
        );
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
}
