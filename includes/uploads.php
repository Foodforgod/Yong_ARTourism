<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function store_image_upload(array $file, string $prefix, int $minimumWidth = 320, int $minimumHeight = 200): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Upload failed or the image exceeds the 8 MB limit.');
    }
    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fileInfo->file($temporaryPath);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Choose a JPEG, PNG, or WebP image.');
    }
    $dimensions = getimagesize($temporaryPath);
    if (!$dimensions || $dimensions[0] < $minimumWidth || $dimensions[1] < $minimumHeight || $dimensions[0] > 10000 || $dimensions[1] > 10000) {
        throw new RuntimeException('The image dimensions are invalid or too small.');
    }
    $filename = preg_replace('/[^a-z0-9-]/', '', strtolower($prefix)) . '-' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $directory = dirname(__DIR__) . '/assets/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The upload directory could not be created.');
    }
    if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
        throw new RuntimeException('The image could not be stored. Check upload directory permissions.');
    }
    return 'assets/uploads/' . $filename;
}
