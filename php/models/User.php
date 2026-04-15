<?php


require_once __DIR__ . '/../config/database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(string $username, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :hash)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':hash'     => $hash,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    public function searchByUsername(string $query, int $excludeUserId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email FROM users WHERE username LIKE :q AND id != :uid LIMIT 20'
        );
        $stmt->execute([':q' => "%$query%", ':uid' => $excludeUserId]);
        return $stmt->fetchAll();
    }
}
