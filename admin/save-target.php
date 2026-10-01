<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    http_response_code(419);
    echo json_encode(['error' => 'Session expired. Refresh the editor and try again.']);
    exit;
}
$posterId = filter_var($_GET['poster'] ?? null, FILTER_VALIDATE_INT);
$bytes = file_get_contents('php://input');
if (!$posterId || strlen($bytes) < 1000 || strlen($bytes) > 25_000_000) {
    http_response_code(400);
    echo json_encode(['error' => 'The compiled target is invalid or exceeds 25 MB.']);
    exit;
}
$statement = db()->prepare('SELECT id FROM ar_posters WHERE id = ?');
$statement->execute([$posterId]);
if (!$statement->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Poster not found.']);
    exit;
}
$filename = 'poster-' . $posterId . '-' . bin2hex(random_bytes(12)) . '.mind';
$directory = dirname(__DIR__) . '/assets/ar-targets';
if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    http_response_code(500);
    echo json_encode(['error' => 'Target storage is unavailable.']);
    exit;
}
$targetPath = $directory . '/' . $filename;
if (file_put_contents($targetPath, $bytes, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'The target file could not be stored.']);
    exit;
}
try {
    db()->prepare("UPDATE ar_posters SET target_file = ?, target_status = 'ready', target_compiled_at = NOW() WHERE id = ?")
        ->execute(['assets/ar-targets/' . $filename, $posterId]);
    record_activity('ar_target_compiled', 'MindAR target compiled for poster #' . $posterId);
} catch (Throwable $exception) {
    unlink($targetPath);
    http_response_code(500);
    echo json_encode(['error' => 'The target status could not be updated.']);
    exit;
}
echo json_encode(['status' => 'ready', 'target' => $filename]);
