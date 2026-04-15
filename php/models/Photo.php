<?php


require_once __DIR__ . '/../config/database.php';

class Photo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO photos (album_id, user_id, original_filename, stored_filename,
                                 mime_type, file_size, width, height, thumbnail_filename)
             VALUES (:album_id, :user_id, :orig, :stored, :mime, :size, :w, :h, :thumb)'
        );
        $stmt->execute([
            ':album_id' => $data['album_id'],
            ':user_id'  => $data['user_id'],
            ':orig'     => $data['original_filename'],
            ':stored'   => $data['stored_filename'],
            ':mime'     => $data['mime_type'],
            ':size'     => $data['file_size'],
            ':w'        => $data['width'],
            ':h'        => $data['height'],
            ':thumb'    => $data['thumbnail_filename'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, u.username AS author_name, a.title AS album_title
             FROM photos p
             JOIN users u ON p.user_id = u.id
             JOIN albums a ON p.album_id = a.id
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $photo = $stmt->fetch();
        return $photo ?: null;
    }

    public function findByAlbum(int $albumId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM photos WHERE album_id = :aid ORDER BY created_at DESC'
        );
        $stmt->execute([':aid' => $albumId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM photos WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    
    public function getFeed(?int $userId, int $limit = 10, int $offset = 0): array
    {
        $sql = '
            SELECT p.*, u.username AS author_name, a.title AS album_title,
                   a.visibility,
                   (SELECT COUNT(*) FROM likes l WHERE l.photo_id = p.id) AS like_count,
                   ' . ($userId ? '(SELECT COUNT(*) FROM likes l2 WHERE l2.photo_id = p.id AND l2.user_id = :uid_like) AS user_liked,' : '0 AS user_liked,') . '
                   (SELECT COUNT(*) FROM comments c WHERE c.photo_id = p.id) AS comment_count
            FROM photos p
            JOIN albums a ON p.album_id = a.id
            JOIN users u ON p.user_id = u.id
            WHERE a.visibility = "public"
        ';

        $params = [];

        if ($userId) {
            $sql .= '
                OR (a.visibility = "shared" AND a.id IN (
                    SELECT album_id FROM album_shares WHERE shared_with_user_id = :uid_shared
                ))
                OR a.user_id = :uid_own
            ';
            $params[':uid_shared'] = $userId;
            $params[':uid_own']    = $userId;
            $params[':uid_like']   = $userId;
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT :lim OFFSET :off';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countFeed(?int $userId): int
    {
        $sql = '
            SELECT COUNT(*) as total
            FROM photos p
            JOIN albums a ON p.album_id = a.id
            WHERE a.visibility = "public"
        ';
        $params = [];

        if ($userId) {
            $sql .= '
                OR (a.visibility = "shared" AND a.id IN (
                    SELECT album_id FROM album_shares WHERE shared_with_user_id = :uid_shared
                ))
                OR a.user_id = :uid_own
            ';
            $params[':uid_shared'] = $userId;
            $params[':uid_own']    = $userId;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (int) $stmt->fetch()['total'];
    }
}
