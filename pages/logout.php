<?php
require_once 'connect.php';
require_once 'authManager.php';

session_start();

// 1. Delete the Persistent Token from the Database
if (isset($_COOKIE['remember_me'])) {
    $db = (new Database())->getConnection();
    $tokenHash = hash('sha256', $_COOKIE['remember_me']);
    
    // Delete this specific token so it can never be used again
    $stmt = $db->prepare("DELETE FROM user_tokens WHERE token_hash = ?");
    $stmt->execute([$tokenHash]);

    // 2. Clear the "Remember Me" cookie from the browser
    setcookie('remember_me', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// 3. Clear all session variables
$_SESSION = array();

// 4. Kill the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Destroy the session on the server
session_destroy();

// 6. Back to login
header("Location: index.php");
exit();
