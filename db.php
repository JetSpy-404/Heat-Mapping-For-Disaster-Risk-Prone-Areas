<?php
// Simple PDO connection helper. Update credentials if needed.
$cfg = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'final_thesis_system',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];

$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
try {
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // In production log the error instead of echoing
    die("Database connection failed: " . $e->getMessage());
}
return $pdo;
