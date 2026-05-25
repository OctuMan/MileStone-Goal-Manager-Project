<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'connect.php';
require_once 'goalManager.php';
require_once 'taskManager.php';
require_once 'achievementManager.php';

function sendResponse($status, $message, $extra = [])
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    exit;
}

if (!isset($_SESSION['user_id'])) {
    sendResponse('error', 'Unauthorized access');
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    sendResponse('error', 'No data received');
}

$db = (new Database())->getConnection();
new goalManager($db);
new taskManager($db);
$achievementMan = new achievementManager($db);
$action = $data['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

switch ($action) {
    case 'fetch_achievements':
        $manual = $achievementMan->getManualAchievements($userId);
        $tasks = $achievementMan->getCompletedTasks($userId);
        $goals = $achievementMan->getCompletedGoals($userId);

        sendResponse('success', 'Achievements retrieved', [
            'manual' => $manual,
            'tasks' => $tasks,
            'goals' => $goals,
            'stats' => [
                'manual' => count($manual),
                'tasks' => count($tasks),
                'goals' => count($goals),
                'total' => count($manual) + count($tasks) + count($goals),
            ],
        ]);
        break;

    case 'insert':
        $title = trim($data['achievement_title'] ?? '');
        $category = strtolower(trim($data['achievement_category'] ?? ''));
        $note = trim($data['achievement_note'] ?? '');
        $achievedAt = trim($data['achieved_at'] ?? '');
        $allowedCategories = ['health', 'work', 'personal', 'religion', 'learning', 'other'];

        if ($title === '') {
            sendResponse('error', 'Achievement title is required');
        }

        if (strlen($title) > 255) {
            sendResponse('error', 'Achievement title is too long');
        }

        if (!in_array($category, $allowedCategories, true)) {
            sendResponse('error', 'Invalid achievement category');
        }

        if ($achievedAt === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $achievedAt)) {
            sendResponse('error', 'Invalid achievement date');
        }

        $achievement = [
            'user_id' => $userId,
            'achievement_title' => $title,
            'achievement_category' => $category,
            'achievement_note' => $note === '' ? null : $note,
            'achieved_at' => $achievedAt,
        ];

        if ($achievementMan->createAchievement($achievement)) {
            sendResponse('inserted', 'Achievement saved');
        }

        sendResponse('error', 'Database insertion failed');
        break;

    case 'delete-achievement':
        $achievementId = $data['achievement_id'] ?? null;

        if ($achievementId && $achievementMan->deleteAchievement((int) $achievementId, $userId)) {
            sendResponse('success', 'Achievement deleted');
        }

        sendResponse('error', 'Delete failed');
        break;

    default:
        sendResponse('error', 'Invalid action');
}
