<?php


require_once __DIR__ . '/../config/database.php';

class Album
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, string $title, string $description, string $visibility): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO albums (user_id, title, description, visibility)
             VALUES (:uid, :title, :desc, :vis)'
        );
        $stmt->execute([
            ':uid'   => $userId,
            ':title' => $title,
            ':desc'  => $description,
            ':vis'   => $visibility,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.username AS owner_name
             FROM albums a
             JOIN users u ON a.user_id = u.id
             WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $album = $stmt->fetch();
        return $album ?: null;
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, COUNT(p.id) AS photo_count
             FROM albums a
             LEFT JOIN photos p ON p.album_id = a.id
             WHERE a.user_id = :uid
             GROUP BY a.id
             ORDER BY a.updated_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function findSharedWithUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.username AS owner_name, s.permission,
                    COUNT(p.id) AS photo_count
             FROM albums a
             JOIN album_shares s ON s.album_id = a.id
             JOIN users u ON a.user_id = u.id
             LEFT JOIN photos p ON p.album_id = a.id
             WHERE s.shared_with_user_id = :uid
             GROUP BY a.id, u.username, s.permission
             ORDER BY a.updated_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function update(int $id, string $title, string $description, string $visibility): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE albums SET title = :title, description = :desc, visibility = :vis
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id'    => $id,
            ':title' => $title,
            ':desc'  => $description,
            ':vis'   => $visibility,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM albums WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function shareWith(int $albumId, int $userId, string $permission = 'read'): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO album_shares (album_id, shared_with_user_id, permission)
             VALUES (:aid, :uid, :perm)
             ON DUPLICATE KEY UPDATE permission = :perm2'
        );
        return $stmt->execute([
            ':aid'   => $albumId,
            ':uid'   => $userId,
            ':perm'  => $permission,
            ':perm2' => $permission,
        ]);
    }

    public function removeShare(int $albumId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM album_shares WHERE album_id = :aid AND shared_with_user_id = :uid'
        );
        return $stmt->execute([':aid' => $albumId, ':uid' => $userId]);
    }

    public function getShares(int $albumId): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, u.username, u.email
             FROM album_shares s
             JOIN users u ON s.shared_with_user_id = u.id
             WHERE s.album_id = :aid'
        );
        $stmt->execute([':aid' => $albumId]);
        return $stmt->fetchAll();
    }

    public function canUserAccess(int $albumId, ?int $userId): bool
    {
        $album = $this->findById($albumId);
        if (!$album) return false;
        if ($album['visibility'] === 'public') return true;
        if ($userId === null) return false;
        if ((int)$album['user_id'] === $userId) return true;

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM album_shares WHERE album_id = :aid AND shared_with_user_id = :uid'
        );
        $stmt->execute([':aid' => $albumId, ':uid' => $userId]);
        return $stmt->fetchColumn() > 0;
    }

    public function canUserEdit(int $albumId, ?int $userId): bool
    {
        if ($userId === null) return false;
        $album = $this->findById($albumId);
        if (!$album) return false;
        if ((int)$album['user_id'] === $userId) return true;

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM album_shares
             WHERE album_id = :aid AND shared_with_user_id = :uid AND permission = "edit"'
        );
        $stmt->execute([':aid' => $albumId, ':uid' => $userId]);
        return $stmt->fetchColumn() > 0;
    }
}
