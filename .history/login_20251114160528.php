<?php
// Ensure session cookie params are set consistently before starting session
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    // 'secure' => true, // uncomment when using HTTPS
    'samesite' => 'Lax',
]);
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: auth-cover-login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if (!$email || !$pass || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: auth-cover-login.html?error=invalid');
    exit;
}

$pdo = include __DIR__ . '/db.php';

try {
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Check if user exists first
    if (!$user) {
        // Log detail server-side for debugging (do not expose to client)
        error_log(sprintf("Login failed: no user found for email=%s, remote=%s", $email, $_SERVER['REMOTE_ADDR'] ?? ''));
        // Use session flash message and redirect to PHP wrapper
        $_SESSION['flash'] = [ 'text' => 'Login failed: email or password incorrect.', 'console' => 'No user found for given email.' ];
        header('Location: auth-cover-login.php');
        exit;
    }

    // Check if user is approved
    if ($user['status'] !== 'approved') {
        $_SESSION['flash'] = [ 'text' => 'Your account is pending approval. Please wait for admin approval.', 'console' => 'User status not approved.' ];
        header('Location: auth-cover-login.php');
        exit;
    }

    // Verify password
    if (!password_verify($pass, $user['password_hash'])) {
        error_log(sprintf("Login failed: invalid password for user_id=%s email=%s, remote=%s", $user['id'], $email, $_SERVER['REMOTE_ADDR'] ?? ''));
        $_SESSION['flash'] = [ 'text' => 'Login failed: email or password incorrect.', 'console' => 'Invalid password provided.' ];
        header('Location: auth-cover-login.php');
        exit;
    }

    // Successful login: regenerate session id and store minimal info in session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_role'] = $user['role'];

    // Set login success greeting with time-based greeting
    $current_hour = date('H');
    $time_greeting = '';

    if ($current_hour >= 5 && $current_hour < 12) {
        $time_greeting = 'Good morning';
    } elseif ($current_hour >= 12 && $current_hour < 17) {
        $time_greeting = 'Good afternoon';
    } elseif ($current_hour >= 17 && $current_hour < 21) {
        $time_greeting = 'Good evening';
    } else {
        $time_greeting = 'Good night';
    }

    $_SESSION['login_greeting'] = $time_greeting . ", " . $user['first_name'] . "!";

    // redirect to PHP-protected dashboard wrapper
    header('Location: dashboard.php');
    exit;
} catch (Exception $e) {
    // In production, log $e->getMessage()
    error_log('Login exception: ' . $e->getMessage());
    $_SESSION['flash'] = [ 'text' => 'Server error. Please try again later.', 'console' => 'Exception during login: ' . $e->getMessage() ];
    header('Location: auth-cover-login.php');
    exit;
}
