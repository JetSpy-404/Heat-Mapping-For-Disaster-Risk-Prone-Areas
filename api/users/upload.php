<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../session_check.php';

// Simple, secure file upload for profile pictures
try {
    if (!isset($_FILES['profile_picture'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['profile_picture'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Upload error']);
        exit;
    }

    // Validate size (limit to 5MB)
    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File too large']);
        exit;
    }

    // Validate MIME/type by extension and finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];
    if (!array_key_exists($mime, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }

    $ext = $allowed[$mime];
    $uploadsDir = __DIR__ . '/../../assets/images/uploads';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

    $basename = bin2hex(random_bytes(8)) . '_' . time();
    $filename = $basename . '.' . $ext;
    $dest = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
        exit;
    }

    // Return web-accessible path
    $webPath = 'assets/images/uploads/' . $filename;
    echo json_encode(['success' => true, 'path' => $webPath]);
} catch (Exception $e) {
    error_log('[upload.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
