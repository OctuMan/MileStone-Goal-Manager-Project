<?php

class achievementManager
{
    private $db;

    public function __construct($databaseConnection)
    {
        $this->db = $databaseConnection;
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS achievements (
            achievement_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            achievement_title VARCHAR(255) NOT NULL,
            achievement_category VARCHAR(40) NOT NULL,
            achievement_note TEXT NULL,
            achieved_at DATE NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_achievements_user (user_id),
            INDEX idx_achievements_date (achieved_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->exec($sql);
    }

    public function createAchievement(array $achievement)
    {
        $stmt = $this->db->prepare("
            INSERT INTO achievements (
                user_id,
                achievement_title,
                achievement_category,
                achievement_note,
                achieved_at
            ) VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $achievement['user_id'],
            $achievement['achievement_title'],
            $achievement['achievement_category'],
            $achievement['achievement_note'],
            $achievement['achieved_at'],
        ]);
    }

    public function getManualAchievements(int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM achievements
            WHERE user_id = ?
            ORDER BY achieved_at DESC, created_at DESC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCompletedTasks(int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                tasks.*,
                goals.goal_name
            FROM tasks
            LEFT JOIN goals
                ON goals.goal_id = tasks.goal_id
                AND goals.user_id = tasks.user_id
            WHERE tasks.user_id = ?
                AND tasks.task_status = 'completed'
            ORDER BY tasks.done_at DESC, tasks.created_at DESC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCompletedGoals(int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM goals
            WHERE user_id = ?
                AND goal_status = 'completed'
            ORDER BY done_at DESC, created_at DESC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteAchievement(int $achievementId, int $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM achievements WHERE achievement_id = ? AND user_id = ?");
        return $stmt->execute([$achievementId, $userId]);
    }
}
