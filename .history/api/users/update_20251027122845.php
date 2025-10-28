<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';
require_once __DIR__ . '/../../db.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
$first_name = trim($input['first_name'] ?? '');
$last_name = trim($input['last_name'] ?? '');
$email = trim($input['email'] ?? '');
$usertype = trim($input['usertype'] ?? $input['role'] ?? 'user');
$status = trim($input['status'] ?? '');
$municipalityName = trim($input['municipality'] ?? '');
$address = trim($input['address'] ?? '');
$contact_number = trim($input['contact_number'] ?? '');

// profile_picture: only update when provided (non-empty)
$profile_picture = array_key_exists('profile_picture', $input) ? trim($input['profile_picture']) : null;

// Simple validation
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid email']);
    exit;
}

if ($contact_number !== '' && !preg_match('/^[0-9+\-() ]{3,32}$/', $contact_number)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid contact number']);
    exit;
}

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing id']);
    exit;
}

if ($first_name === '' || $last_name === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Required fields missing']);
    exit;
}

try {
    // Authorization: only admins may change role or status
    $currentRole = $_SESSION['user_role'] ?? 'user';
    if (($usertype !== '' && $usertype !== null && $currentRole !== 'admin') || ($status !== '' && $status !== null && $currentRole !== 'admin')) {
        // non-admin attempting to change role or status -> forbidden
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }

    // Resolve municipality name to id if provided, otherwise keep NULL
    $municipality_id = null;
    if ($municipalityName !== '') {
        $stmt = $pdo->prepare('SELECT id FROM municipalities WHERE name = ? LIMIT 1');
        $stmt->execute([$municipalityName]);
        $m = $stmt->fetch();
        if ($m) $municipality_id = (int)$m['id'];
    }

    // Update only existing columns in the users table (include new address/contact_number)
    // Include profile_picture in the update if provided (can be empty string)
    // Build dynamic update: only include profile_picture if provided (not null)
    $updateFields = ['first_name = ?', 'last_name = ?', 'email = ?', 'role = ?', 'status = ?', 'municipality_id = ?', 'address = ?', 'contact_number = ?'];
    $params = [$first_name, $last_name, $email, $usertype, $status, $municipality_id, $address, $contact_number];
    if ($profile_picture !== null) {
        $updateFields[] = 'profile_picture = ?';
        $params[] = $profile_picture;
    }
    $params[] = $id;
    $sql = 'UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Fetch authoritative row and map to front-end shape
    $stmt = $pdo->prepare('SELECT u.id, u.first_name, u.last_name, u.email, u.role AS usertype, u.status, COALESCE(m.name, "") AS municipality, u.address, u.contact_number, u.profile_picture, u.created_at FROM users u LEFT JOIN municipalities m ON u.municipality_id = m.id WHERE u.id = ?');
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    if (!$r) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $row = [
        'id' => (int)$r['id'],
        'profile_picture' => $r['profile_picture'] ? $r['profile_picture'] : 'assets/images/default-avatar.jpg',
        'first_name' => $r['first_name'],
        'last_name' => $r['last_name'],
        'municipality' => $r['municipality'],
        'address' => $r['address'],
        'contact_number' => $r['contact_number'],
        'usertype' => $r['usertype'],
        'status' => $r['status'],
        'email' => $r['email'],
        'created_at' => $r['created_at'],
    ];

    echo json_encode(['success' => true, 'data' => $row]);
} catch (Exception $e) {
    // Log the exception message for server-side debugging, but don't leak details to client
    error_log('[api/users/update.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
