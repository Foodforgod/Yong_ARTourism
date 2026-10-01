<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function require_admin(): array
{
    $adminId = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$adminId) {
        header('Location: ' . app_url('admin/login.php'));
        exit;
    }

    $statement = db()->prepare("SELECT id, username, email FROM users WHERE id = ? AND role = 'admin' AND status = 'active'");
    $statement->execute([$adminId]);
    $admin = $statement->fetch();
    if (!$admin) {
        unset($_SESSION['admin_id']);
        header('Location: ' . app_url('admin/login.php'));
        exit;
    }
    return $admin;
}
