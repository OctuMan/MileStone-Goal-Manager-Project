<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'connect.php';
require_once 'taskManager.php';
require_once 'goalManager.php';

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
$taskMan = new taskManager($db);
$goalMan = new goalManager($db);
$action = $data['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

switch ($action) {
    case 'insert':
        $title = trim($data['task_title'] ?? '');
        $notes = trim($data['task_notes'] ?? '');
        $dueDate = trim($data['due_date'] ?? '');
        $goalId = $data['goal_id'] ?? null;

        if ($title === '') {
            sendResponse('error', 'Task title is required');
        }

        if (strlen($title) > 255) {
            sendResponse('error', 'Task title is too long');
        }

        if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            sendResponse('error', 'Invalid due date');
        }

        if ($goalId === '' || $goalId === 'none') {
            $goalId = null;
        }

        if ($goalId !== null) {
            $goalId = (int) $goalId;
            if (!$taskMan->userOwnsGoal($goalId, $userId)) {
                sendResponse('error', 'Invalid goal');
            }
        }

        $task = [
            'user_id' => $userId,
            'goal_id' => $goalId,
            'task_title' => $title,
            'task_notes' => $notes === '' ? null : $notes,
            'task_status' => 'pending',
            'due_date' => $dueDate === '' ? null : $dueDate,
        ];

        if ($taskMan->createTask($task)) {
            if ($goalId !== null) {
                $goalMan->syncGoalStatusFromTasks($goalId, $userId);
            }
            sendResponse('inserted', 'Task successfully inserted');
        }

        sendResponse('error', 'Database insertion failed');
        break;

    case 'fetch_tasks':
        sendResponse('success', 'Tasks retrieved', ['tasks' => $taskMan->getUserTasks($userId)]);
        break;

    case 'toggle-status':
        $taskId = $data['task_id'] ?? null;
        $currentStatus = $data['current_status'] ?? 'pending';

        if (!in_array($currentStatus, ['pending', 'completed'], true)) {
            sendResponse('error', 'Invalid task status');
        }

        $nextStatus = ($currentStatus === 'pending') ? 'completed' : 'pending';

        if ($taskId) {
            $linkedGoalId = $taskMan->getTaskGoalId((int) $taskId, $userId);
        }

        if ($taskId && $taskMan->toggleStatus((int) $taskId, $userId, $nextStatus)) {
            if ($linkedGoalId) {
                $goalMan->syncGoalStatusFromTasks($linkedGoalId, $userId);
            }
            sendResponse('success', "Task marked as $nextStatus", ['new_status' => $nextStatus]);
        }

        sendResponse('error', 'Toggle failed');
        break;

    case 'delete-task':
        $taskId = $data['task_id'] ?? null;

        if ($taskId) {
            $linkedGoalId = $taskMan->getTaskGoalId((int) $taskId, $userId);
        }

        if ($taskId && $taskMan->deleteTask((int) $taskId, $userId)) {
            if ($linkedGoalId) {
                $goalMan->syncGoalStatusFromTasks($linkedGoalId, $userId);
            }
            sendResponse('success', 'Task deleted successfully');
        }

        sendResponse('error', 'Delete failed');
        break;

    default:
        sendResponse('error', 'Invalid action');
}
