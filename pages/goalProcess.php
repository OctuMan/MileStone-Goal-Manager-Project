<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'connect.php';
require_once 'goalManager.php';
require_once 'auth.php'; 

// 1. Enforce Login at the top
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    sendResponse('error', 'No data received');
}

$db = (new Database())->getConnection();
$goalMan = new goalManager($db);
$action = $data['action'] ?? '';

function sendResponse($status, $message, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(["status" => $status, "message" => $message], $extra));
    exit;
}

switch ($action) {
    case 'insert': 
        // Force the user_id from the SESSION, not the JSON payload (Security!)
        $data['user_id'] = $_SESSION['user_id'];
        $allowedCategories = ['health', 'work', 'personal', 'religion', 'other'];
        $data['goal_status'] = 'pending';
        $data['goal_category'] = strtolower(trim($data['goal_category'] ?? ''));
        
        // Basic Validation
        if (empty($data['goal_name']) || empty($data['goal_category'])) {
            sendResponse('error', 'Goal name and category are required');
        }

        if (!in_array($data['goal_category'], $allowedCategories, true)) {
            sendResponse('error', 'Invalid goal category');
        }

        if ($goalMan->InsertGoal($data)) {
            sendResponse('inserted', "Goal successfully inserted");
        } else {
            sendResponse("error", "Database insertion failed");
        }
        break;
    case 'fetch_goals':
        $userId = $_SESSION['user_id'];
        $goals = $goalMan->getUserGoals($userId); // Make sure this method exists in your GoalManager!
        
        // Send the raw data back to JavaScript
        sendResponse('success', 'Goals retrieved', ['goals' => $goals]);
        break;

    case 'toggle-status':
    $goalId = $data['goal_id'] ?? null;
    $currentStatus = $data['current_status'] ?? 'pending';
    $userId = $_SESSION['user_id'];
    $allowedStatuses = ['pending', 'completed'];

    if (!in_array($currentStatus, $allowedStatuses, true)) {
        sendResponse('error', 'Invalid goal status');
    }

    // Toggle logic: if it's currently pending, make it completed, and vice-versa
    $nextStatus = ($currentStatus === 'pending') ? 'completed' : 'pending';

    if ($goalId && $goalMan->toggleStatus((int)$goalId, (int)$userId, $nextStatus)) {
        sendResponse('success', "Goal marked as $nextStatus", ['new_status' => $nextStatus]);
    } else {
        sendResponse('error', 'Toggle failed');
    }
    break;
    case 'delete-goal':
        $goalId = $data['goal_id'] ?? null;
        $userId = $_SESSION['user_id'];
        if($goalId && $goalMan->deleteGoal((int)$goalId, (int)$userId)){
             sendResponse('success', "Goal deleted successfully");
    } else {
        sendResponse('error', 'Toggle failed');
    }
    break;
    default: 
        sendResponse('error', "Invalid action");
}
