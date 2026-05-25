<?php

class analyticsManager
{
    private $db;

    public function __construct($databaseConnection)
    {
        $this->db = $databaseConnection;
        $this->ensureTables();
    }

    private function ensureTables()
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS tasks (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->exec("CREATE TABLE IF NOT EXISTS achievements (
            achievement_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            achievement_title VARCHAR(255) NOT NULL,
            achievement_category VARCHAR(40) NOT NULL,
            achievement_note TEXT NULL,
            achieved_at DATE NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_achievements_user (user_id),
            INDEX idx_achievements_date (achieved_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getAnalytics(int $userId)
    {
        $goals = $this->fetchAll("SELECT * FROM goals WHERE user_id = ?", [$userId]);
        $tasks = $this->fetchAll("
            SELECT tasks.*, goals.goal_name, goals.goal_category
            FROM tasks
            LEFT JOIN goals
                ON goals.goal_id = tasks.goal_id
                AND goals.user_id = tasks.user_id
            WHERE tasks.user_id = ?
        ", [$userId]);
        $achievements = $this->fetchAll("SELECT * FROM achievements WHERE user_id = ?", [$userId]);

        $goalTotal = count($goals);
        $taskTotal = count($tasks);
        $manualTotal = count($achievements);
        $goalsCompleted = $this->countWhere($goals, fn($goal) => $goal['goal_status'] === 'completed');
        $tasksCompleted = $this->countWhere($tasks, fn($task) => $task['task_status'] === 'completed');
        $tasksPending = $taskTotal - $tasksCompleted;
        $today = new DateTimeImmutable('today');

        $overdueTasks = array_values(array_filter($tasks, function ($task) use ($today) {
            return $task['task_status'] !== 'completed'
                && !empty($task['due_date'])
                && new DateTimeImmutable($task['due_date']) < $today;
        }));

        $upcomingTasks = array_values(array_filter($tasks, function ($task) use ($today) {
            if ($task['task_status'] === 'completed' || empty($task['due_date'])) {
                return false;
            }
            $due = new DateTimeImmutable($task['due_date']);
            return $due >= $today && $due <= $today->modify('+7 days');
        }));

        $categoryStats = $this->categoryStats($goals, $tasks, $achievements);
        $weeklyTrend = $this->weeklyTrend($tasks, $goals, $achievements);
        $stuckGoals = $this->stuckGoals($goals, $tasks);
        $nextAction = $this->nextAction($overdueTasks, $upcomingTasks, $stuckGoals, $tasksPending);

        return [
            'summary' => [
                'goals_total' => $goalTotal,
                'goals_completed' => $goalsCompleted,
                'tasks_total' => $taskTotal,
                'tasks_completed' => $tasksCompleted,
                'tasks_pending' => $tasksPending,
                'manual_achievements' => $manualTotal,
                'overdue_tasks' => count($overdueTasks),
                'upcoming_tasks' => count($upcomingTasks),
                'goal_completion_rate' => $goalTotal ? round(($goalsCompleted / $goalTotal) * 100) : 0,
                'task_completion_rate' => $taskTotal ? round(($tasksCompleted / $taskTotal) * 100) : 0,
            ],
            'category_stats' => $categoryStats,
            'weekly_trend' => $weeklyTrend,
            'stuck_goals' => array_slice($stuckGoals, 0, 5),
            'overdue_tasks_list' => array_slice($overdueTasks, 0, 5),
            'upcoming_tasks_list' => array_slice($upcomingTasks, 0, 5),
            'next_action' => $nextAction,
        ];
    }

    private function fetchAll(string $sql, array $params)
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function countWhere(array $items, callable $callback)
    {
        return count(array_filter($items, $callback));
    }

    private function categoryStats(array $goals, array $tasks, array $achievements)
    {
        $categories = ['health', 'work', 'personal', 'religion', 'learning', 'other'];
        $stats = [];

        foreach ($categories as $category) {
            $stats[$category] = [
                'category' => $category,
                'goals' => 0,
                'completed_goals' => 0,
                'tasks' => 0,
                'completed_tasks' => 0,
                'manual_achievements' => 0,
            ];
        }

        foreach ($goals as $goal) {
            $category = strtolower($goal['goal_category'] ?? 'other');
            $category = isset($stats[$category]) ? $category : 'other';
            $stats[$category]['goals']++;
            if ($goal['goal_status'] === 'completed') {
                $stats[$category]['completed_goals']++;
            }
        }

        foreach ($tasks as $task) {
            $category = strtolower($task['goal_category'] ?? 'other');
            $category = isset($stats[$category]) ? $category : 'other';
            $stats[$category]['tasks']++;
            if ($task['task_status'] === 'completed') {
                $stats[$category]['completed_tasks']++;
            }
        }

        foreach ($achievements as $achievement) {
            $category = strtolower($achievement['achievement_category'] ?? 'other');
            $category = isset($stats[$category]) ? $category : 'other';
            $stats[$category]['manual_achievements']++;
        }

        return array_values($stats);
    }

    private function weeklyTrend(array $tasks, array $goals, array $achievements)
    {
        $weeks = [];
        $start = new DateTimeImmutable('monday this week');

        for ($i = 5; $i >= 0; $i--) {
            $weekStart = $start->modify("-$i weeks");
            $key = $weekStart->format('o-\WW');
            $weeks[$key] = [
                'label' => $weekStart->format('M j'),
                'tasks' => 0,
                'goals' => 0,
                'manual' => 0,
                'total' => 0,
            ];
        }

        foreach ($tasks as $task) {
            if ($task['task_status'] !== 'completed' || empty($task['done_at'])) {
                continue;
            }
            $this->addToWeek($weeks, $task['done_at'], 'tasks');
        }

        foreach ($goals as $goal) {
            if ($goal['goal_status'] !== 'completed' || empty($goal['done_at'])) {
                continue;
            }
            $this->addToWeek($weeks, $goal['done_at'], 'goals');
        }

        foreach ($achievements as $achievement) {
            $this->addToWeek($weeks, $achievement['achieved_at'], 'manual');
        }

        return array_values($weeks);
    }

    private function addToWeek(array &$weeks, string $dateValue, string $type)
    {
        $date = new DateTimeImmutable($dateValue);
        $weekStart = $date->modify('monday this week');
        $key = $weekStart->format('o-\WW');

        if (!isset($weeks[$key])) {
            return;
        }

        $weeks[$key][$type]++;
        $weeks[$key]['total']++;
    }

    private function stuckGoals(array $goals, array $tasks)
    {
        $taskMap = [];

        foreach ($tasks as $task) {
            if (!$task['goal_id']) {
                continue;
            }

            $goalId = (int) $task['goal_id'];
            if (!isset($taskMap[$goalId])) {
                $taskMap[$goalId] = ['total' => 0, 'completed' => 0, 'next_due' => null];
            }

            $taskMap[$goalId]['total']++;
            if ($task['task_status'] === 'completed') {
                $taskMap[$goalId]['completed']++;
            }
            if ($task['task_status'] !== 'completed' && !empty($task['due_date'])) {
                if (!$taskMap[$goalId]['next_due'] || $task['due_date'] < $taskMap[$goalId]['next_due']) {
                    $taskMap[$goalId]['next_due'] = $task['due_date'];
                }
            }
        }

        $stuck = [];
        foreach ($goals as $goal) {
            if ($goal['goal_status'] === 'completed') {
                continue;
            }

            $goalId = (int) $goal['goal_id'];
            $stats = $taskMap[$goalId] ?? ['total' => 0, 'completed' => 0, 'next_due' => null];
            $progress = $stats['total'] ? round(($stats['completed'] / $stats['total']) * 100) : 0;

            if ($progress < 60) {
                $goal['tasks_total'] = $stats['total'];
                $goal['tasks_completed'] = $stats['completed'];
                $goal['progress'] = $progress;
                $goal['next_due'] = $stats['next_due'];
                $stuck[] = $goal;
            }
        }

        usort($stuck, fn($a, $b) => $a['progress'] <=> $b['progress']);
        return $stuck;
    }

    private function nextAction(array $overdueTasks, array $upcomingTasks, array $stuckGoals, int $tasksPending)
    {
        if (!empty($overdueTasks)) {
            return [
                'type' => 'overdue',
                'title' => $overdueTasks[0]['task_title'],
                'detail' => $overdueTasks[0]['goal_name'] ?? null,
            ];
        }

        if (!empty($upcomingTasks)) {
            return [
                'type' => 'upcoming',
                'title' => $upcomingTasks[0]['task_title'],
                'detail' => $upcomingTasks[0]['due_date'],
            ];
        }

        if (!empty($stuckGoals)) {
            return [
                'type' => 'stuck_goal',
                'title' => $stuckGoals[0]['goal_name'],
                'detail' => $stuckGoals[0]['progress'] . '%',
            ];
        }

        return [
            'type' => $tasksPending > 0 ? 'pending' : 'clear',
            'title' => null,
            'detail' => null,
        ];
    }
}
