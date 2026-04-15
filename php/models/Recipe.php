<?php


require_once __DIR__ . '/../config/database.php';

class Recipe
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getByPhoto(int $photoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM recipes WHERE photo_id = :pid ORDER BY step_order ASC'
        );
        $stmt->execute([':pid' => $photoId]);
        $rows = $stmt->fetchAll();

        
        return array_map(function ($row) {
            $row['parameters'] = json_decode($row['parameters'], true);
            return $row;
        }, $rows);
    }

    public function addStep(int $photoId, int $userId, int $order, string $filterName, array $params): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO recipes (photo_id, user_id, step_order, filter_name, parameters)
             VALUES (:pid, :uid, :ord, :fname, :params)'
        );
        $stmt->execute([
            ':pid'    => $photoId,
            ':uid'    => $userId,
            ':ord'    => $order,
            ':fname'  => $filterName,
            ':params' => json_encode($params),
        ]);
        return (int) $this->db->lastInsertId();
    }

    
    public function replaceAll(int $photoId, int $userId, array $steps): bool
    {
        $this->db->beginTransaction();
        try {
            
            $del = $this->db->prepare('DELETE FROM recipes WHERE photo_id = :pid');
            $del->execute([':pid' => $photoId]);

            
            $ins = $this->db->prepare(
                'INSERT INTO recipes (photo_id, user_id, step_order, filter_name, parameters)
                 VALUES (:pid, :uid, :ord, :fname, :params)'
            );

            foreach ($steps as $index => $step) {
                $ins->execute([
                    ':pid'    => $photoId,
                    ':uid'    => $userId,
                    ':ord'    => $index + 1,
                    ':fname'  => $step['filter_name'],
                    ':params' => json_encode($step['parameters'] ?? []),
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteByPhoto(int $photoId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM recipes WHERE photo_id = :pid');
        return $stmt->execute([':pid' => $photoId]);
    }
}
