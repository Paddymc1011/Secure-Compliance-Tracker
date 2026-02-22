<?php
// Include the database connection from dbcon.php
require_once __DIR__ . '/dbcon.php';

// Check if a session is already active before starting a new one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
}

// Simple helper to output escaped HTML
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// CSRF token helper
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    return $_SESSION['csrf_token'];
}
