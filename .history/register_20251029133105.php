<?php
header('Content-Type: application/json');

// Check if request is AJAX/fetch
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    header('Location: auth-cover-register.php');
    exit;
}

$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';
$role  = $_POST['role'] ?? 'user';
$municipality_id = $_POST['municipality_id'] ?? '';
$address = trim($_POST['address'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');

if (!$first || !$last || !$email || !$pass || !$municipality_id || !$address || !$contact_number || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['administrator','user'])) {
    $error = 'Invalid input. Please complete all required fields.';
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
    session_start();
    $_SESSION['flash'] = [ 'text' => $error, 'console' => 'Registration validation failed (missing/invalid field).' ];
    header('Location: auth-cover-register.php');
    exit;
}

$pdo = include __DIR__ . '/db.php';

try {
    // check duplicate email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = 'An account with that email already exists.';
        if ($isAjax) {
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
        session_start();
        $_SESSION['flash'] = [ 'text' => $error, 'console' => 'Duplicate email attempted on registration.' ];
        header('Location: auth-cover-register.php');
        exit;
    }

    $password_hash = password_hash($pass, PASSWORD_DEFAULT);

    $ins = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, status, municipality_id, address, contact_number, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $ins->execute([$first, $last, $email, $password_hash, $role, 'pending', $municipality_id, $address, $contact_number]);

    $successMessage = 'Registration successful. Your account is pending approval. You will be notified once approved.';
    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => $successMessage]);
        exit;
    }

    // Redirect to login on success with flash message
    session_start();
    $_SESSION['flash'] = [ 'text' => $successMessage, 'console' => 'New user registered: ' . $email ];
    header('Location: auth-cover-login.php');
    exit;
} catch (Exception $e) {
    // In production, log $e->getMessage()
    error_log('Registration exception: ' . $e->getMessage());
    $error = 'Server error. Please try again later.';
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
    session_start();
    $_SESSION['flash'] = [ 'text' => $error, 'console' => 'Registration exception: ' . $e->getMessage() ];
    header('Location: auth-cover-register.php');
    exit;
}
