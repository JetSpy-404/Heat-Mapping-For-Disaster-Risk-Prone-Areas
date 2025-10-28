<?php
// logout.php - destroys session and redirects to login
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    // 'secure' => true,
    'samesite' => 'Lax',
]);
session_start();

// Unset all session variables
$_SESSION = [];

// Delete session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'] ?? '',
        $params['secure'] ?? false, $params['httponly'] ?? false
    );
}

// Destroy the session
session_destroy();

// Redirect to login wrapper page
header('Location: auth-cover-login.php?logged_out=1');
exit;
