<?php

class taskManager
{
    private $db;

    public function __construct($databaseConnection)
    {
        $this->db = $databaseConnection;
        $this->ensureTable();
    }

    private function ensureTable()
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

    public function createTask(array $task)
    {
        $stmt = $this->db->prepare("
            INSERT INTO tasks (user_id, goal_id, task_title, task_notes, task_status, due_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $task['user_id'],
            $task['goal_id'],
            $task['task_title'],
            $task['task_notes'],
            $task['task_status'],
            $task['due_date'],
        ]);
    }

    public function getUserTasks(int $userId)
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
            ORDER BY
                tasks.task_status = 'completed',
                tasks.due_date IS NULL,
                tasks.due_date ASC,
                tasks.created_at DESC
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggleStatus(int $taskId, int $userId, string $newStatus)
    {
        $doneAt = ($newStatus === 'completed') ? date('Y-m-d H:i:s') : null;
        $stmt = $this->db->prepare("
            UPDATE tasks
            SET task_status = ?, done_at = ?
            WHERE task_id = ? AND user_id = ?
        ");

        return $stmt->execute([$newStatus, $doneAt, $taskId, $userId]);
    }

    public function getTaskGoalId(int $taskId, int $userId)
    {
        $stmt = $this->db->prepare("SELECT goal_id FROM tasks WHERE task_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$taskId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['goal_id'] ? (int) $row['goal_id'] : null;
    }

    public function deleteTask(int $taskId, int $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE task_id = ? AND user_id = ?");
        return $stmt->execute([$taskId, $userId]);
    }

    public function userOwnsGoal(int $goalId, int $userId)
    {
        $stmt = $this->db->prepare("SELECT goal_id FROM goals WHERE goal_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$goalId, $userId]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
