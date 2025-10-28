<?php
header('Content-Type: application/json');
// Minimal registration handler
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
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
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid input. Please complete all required fields.']);
    exit;
}

$pdo = include __DIR__ . '/db.php';

try {
    // check duplicate email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'An account with that email already exists.']);
        exit;
    }

    $password_hash = password_hash($pass, PASSWORD_DEFAULT);

    $ins = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, status, municipality_id, address, contact_number, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $ins->execute([$first, $last, $email, $password_hash, $role, 'pending', $municipality_id, $address, $contact_number]);

    echo json_encode(['success' => true, 'message' => 'Registration successful. Your account is pending approval. You will be notified once approved.']);
} catch (Exception $e) {
    error_log('Registration exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again later.']);
}
