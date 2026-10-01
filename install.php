<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Create administrator';
$message = '';
$complete = false;
try {
    $adminExists = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    if ($adminExists) {
        $complete = true;
        $message = 'An administrator account is already configured. Sign in to continue.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) {
            $message = 'Use 3-80 letters, numbers, dots, underscores, or hyphens for the username.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Enter a valid email address.';
        } elseif (strlen($password) < 12) {
            $message = 'Use a password with at least 12 characters.';
        } else {
            $statement = db()->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
            $statement->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int) db()->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $userId;
            record_activity('admin_created', 'Initial administrator account created');
            header('Location: ' . app_url('admin/index.php'));
            exit;
        }
    }
} catch (Throwable $error) {
    $message = 'Database is not ready. Import database/database.sql, then check the credentials in includes/config.php.';
}
require __DIR__ . '/includes/header.php';
?>
<section class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><p class="eyebrow eyebrow-dark">ONE-TIME SETUP</p><h1 class="h2 mb-3">Create your administrator</h1><p class="text-secondary">This setup page locks after the first account is created.</p>
<?php if ($message): ?><div class="alert <?= $complete ? 'alert-success' : 'alert-info' ?>" role="status"><?= e($message) ?></div><?php endif; ?>
<?php if (!$complete && str_contains($message, 'Database is not ready') === false): ?>
<form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="form-label" for="username">Username</label><input class="form-control mb-3" id="username" name="username" autocomplete="username" minlength="3" maxlength="80" required><label class="form-label" for="email">Email</label><input class="form-control mb-3" id="email" name="email" type="email" autocomplete="email" required><label class="form-label" for="password">Password</label><input class="form-control mb-3" id="password" name="password" type="password" autocomplete="new-password" minlength="12" required><div class="form-text mb-3">At least 12 characters. This password is stored as a one-way hash.</div><button class="btn btn-success" type="submit">Create administrator</button></form>
<?php endif; ?><a class="d-inline-block mt-3" href="<?= e(app_url('admin/login.php')) ?>">Go to sign in</a></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
