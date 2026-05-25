<?php
// auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function checkPersistentSession($authManager) {
    // If the session is empty but the "VIP Pass" (cookie) exists
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
        $user = $authManager->loginWithToken($_COOKIE['remember_me']);
        
        if ($user) {
            // Re-fill the guest list (session)
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['user_role'] = $user->isAdmin() ? 'Admin' : 'User';
            return true;
        }
    }
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}