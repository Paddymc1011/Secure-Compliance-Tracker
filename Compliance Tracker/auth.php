<?php
require_once __DIR__ . '/config.php';

// Authenticate user by username and password
function attempt_login($identifier, $password) {
    global $connection;
    $sql = "SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session id to prevent fixation
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function require_role($role) {
    require_login();
    if (empty($_SESSION['role']) || $_SESSION['role'] !== $role) {
        // Debugging output for session data
        error_log('403 Forbidden: Access denied. Session role: ' . ($_SESSION['role'] ?? 'not set'));
        http_response_code(403);
        echo "<h2>403 Forbidden</h2><p>You do not have permission to access this page.</p>";
        exit;
    }
}

function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
