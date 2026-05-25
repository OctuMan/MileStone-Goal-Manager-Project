<?php
// Line 1
session_start();

$allowed_langs = ['en', 'ar'];

$lang = $_GET['lang'] ?? 'en';

if (in_array($lang, $allowed_langs, true)) {
    $_SESSION['lang'] = $lang;
}

$referrer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
$redirect = 'dashboard.php';

if ($referrer) {
    $refPath = parse_url($referrer, PHP_URL_PATH);
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    $refHost = parse_url($referrer, PHP_URL_HOST);

    if (!$refHost || $refHost === $currentHost) {
        $basename = basename($refPath ?: '');
        if (in_array($basename, ['index.php', 'dashboard.php', 'tasks.php', 'achievements.php', 'analytics.php'], true)) {
            $redirect = $basename;
        }
    }
}

header("Location: $redirect");
exit;
