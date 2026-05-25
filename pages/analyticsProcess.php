<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'connect.php';
require_once 'analyticsManager.php';

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

$action = $data['action'] ?? '';
$db = (new Database())->getConnection();
$analyticsMan = new analyticsManager($db);

if ($action === 'fetch_analytics') {
    sendResponse('success', 'Analytics retrieved', [
        'analytics' => $analyticsMan->getAnalytics((int) $_SESSION['user_id']),
    ]);
}

sendResponse('error', 'Invalid action');
