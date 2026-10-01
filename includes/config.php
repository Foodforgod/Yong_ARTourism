<?php

declare(strict_types=1);

const APP_NAME = 'AR Tourism Explorer';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'yong_artourism';
const DB_USER = 'root';
const DB_PASS = '';
const MAX_UPLOAD_BYTES = 8_000_000;

$projectRoot = realpath(dirname(__DIR__));
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$basePath = '';
if ($projectRoot !== false && $documentRoot !== false) {
    $normalizedRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $normalizedProject = str_replace('\\', '/', $projectRoot);
    if (str_starts_with(strtolower($normalizedProject), strtolower($normalizedRoot . '/'))) {
        $basePath = substr($normalizedProject, strlen($normalizedRoot));
    }
}
define('APP_BASE_PATH', rtrim($basePath, '/'));

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => APP_BASE_PATH !== '' ? APP_BASE_PATH . '/' : '/',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
