<?php
// Central session check include
// Usage: include_once __DIR__ . '/session_check.php';

// Ensure consistent cookie params for sessions. Must be set before session_start().
session_set_cookie_params([
    'lifetime' => 0, // session cookie
    'path' => '/',
    'httponly' => true,
    // 'secure' => true, // enable if using HTTPS
    'samesite' => 'Lax',
]);
session_start();

// Basic session validity checks
$session_ttl = 60 * 60 * 4; // 4 hours session TTL (adjustable)

// Optional fingerprint to make session cookies harder to hijack.
// Use only the User-Agent to avoid invalidating sessions when client IP changes.
$fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? ''));

// If session creation time is missing, set it now
if (empty($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
}

// If session is too old, destroy it and require re-login
if (time() - $_SESSION['created_at'] > $session_ttl) {
    // expire
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'] ?? '',
            $params['secure'] ?? false, $params['httponly'] ?? false
        );
    }
    session_destroy();
    // ensure session lock is released before returning to caller
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_write_close();
    }
    // If request expects JSON or is an API call, return JSON instead of HTML redirect
    $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
    $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) || (strpos($accept, 'application/json') !== false) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    if ($isApi) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'session_expired']);
        exit;
    } else {
        header('Location: auth-cover-login.php?error=session_expired');
        exit;
    }
}

// store fingerprint on first auth
if (!empty($_SESSION['user_id']) && empty($_SESSION['fingerprint'])) {
    $_SESSION['fingerprint'] = $fingerprint;
}

// If user is not authenticated or fingerprint mismatch, redirect to login wrapper
if (empty($_SESSION['user_id']) || (!empty($_SESSION['fingerprint']) && $_SESSION['fingerprint'] !== $fingerprint)) {
    $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
    $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) || (strpos($accept, 'application/json') !== false) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    if ($isApi) {
        // release session lock before responding to avoid blocking parallel AJAX calls
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'auth']);
        exit;
    } else {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        header('Location: auth-cover-login.php?error=auth');
        exit;
    }
}

// Optionally, you can check roles here e.g. if ($_SESSION['role'] !== 'administrator') { ... }

// Release the session lock now that checks are complete. Call this so long-running requests
// or parallel AJAX requests won't be blocked waiting for the session file lock.
if (session_status() === PHP_SESSION_ACTIVE) {
    @session_write_close();
}
