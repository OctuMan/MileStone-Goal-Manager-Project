<?php

class goalManager
{

    private $db;

    public function __construct($databaseConnection)
    {
        $this->db = $databaseConnection;
        $this->ensureTaskTable();
    }

    private function ensureTaskTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tasks (
            task_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            goal_id INT NULL,
            task_title VARCHAR(255) NOT NULL,
            task_notes TEXT NULL,
            task_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            due_date DATE NULL,
            done_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tasks_user (user_id),
            INDEX idx_tasks_goal (goal_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->exec($sql);
    }

    public function InsertGoal(array $goal)
    {
        $stmt = $this->db->prepare("INSERT INTO `goals` (`user_id`, `goal_name`, `goal_status`, `goal_category`) VALUES (?, ?,?, ?);");
        return $stmt->execute([$goal['user_id'], $goal['goal_name'], $goal['goal_status'], $goal['goal_category']]);
    }
    public function getUserGoals($userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                goals.*,
                COALESCE(task_stats.tasks_total, 0) AS tasks_total,
                COALESCE(task_stats.tasks_completed, 0) AS tasks_completed
            FROM goals
            LEFT JOIN (
                SELECT
                    goal_id,
                    user_id,
                    COUNT(*) AS tasks_total,
                    SUM(CASE WHEN task_status = 'completed' THEN 1 ELSE 0 END) AS tasks_completed
                FROM tasks
                WHERE goal_id IS NOT NULL
                GROUP BY goal_id, user_id
            ) AS task_stats
                ON task_stats.goal_id = goals.goal_id
                AND task_stats.user_id = goals.user_id
            WHERE goals.user_id = ?
            ORDER BY goals.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function syncGoalStatusFromTasks(int $goalId, int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS tasks_total,
                SUM(CASE WHEN task_status = 'completed' THEN 1 ELSE 0 END) AS tasks_completed
            FROM tasks
            WHERE goal_id = ? AND user_id = ?
        ");
        $stmt->execute([$goalId, $userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (int) ($stats['tasks_total'] ?? 0);
        $completed = (int) ($stats['tasks_completed'] ?? 0);

        $newStatus = ($total > 0 && $completed === $total) ? 'completed' : 'pending';
        return $this->toggleStatus($goalId, $userId, $newStatus);
    }
    public function toggleStatus(int $goalId, int $userId, string $newStatus)
{
    
    $doneAt = ($newStatus === 'completed') ? date('Y-m-d H:i:s') : null;

    $sql = "UPDATE goals 
            SET goal_status = ?, done_at = ? 
            WHERE goal_id = ? AND user_id = ?";
            
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$newStatus, $doneAt, $goalId, $userId]);
}

    public function deleteGoal(int $goalId, int $userId){
        $sql = "DELETE FROM goals WHERE goal_id = ? AND user_id = ?";
        $stmt = $this-> db->prepare($sql);
        return $stmt->execute([$goalId, $userId]);
    }
}
