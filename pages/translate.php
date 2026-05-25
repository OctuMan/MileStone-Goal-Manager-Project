<?php
// translate.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Define it clearly
$current_lang = $_SESSION['lang'] ?? 'en';

$json_path = __DIR__ . "/../lang/{$current_lang}.json";
$translations = [];

if (file_exists($json_path)) {
    $translations = json_decode(file_get_contents($json_path), true);
}

function __($key) {
    global $translations;
    $keys = explode('.', $key);
    $temp = $translations;

    foreach ($keys as $k) {
        if (isset($temp[$k])) {
            $temp = $temp[$k];
        } else {
            return $key; 
        }
    }
    return $temp;
}