<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Administrator sign in';
$error = '';
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . app_url('admin/index.php'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $now = time();
    $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'started' => $now];
    if ($now - (int) $attempts['started'] > 900) {
        $attempts = ['count' => 0, 'started' => $now];
    }
    if ((int) $attempts['count'] >= 8) {
        $error = 'Too many attempts. Wait 15 minutes before trying again.';
    } else {
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $statement = db()->prepare("SELECT id, password_hash FROM users WHERE (username = ? OR email = ?) AND role = 'admin' AND status = 'active' LIMIT 1");
        $statement->execute([$identity, $identity]);
        $user = $statement->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $user['id'];
            unset($_SESSION['login_attempts']);
            db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
            record_activity('admin_login', 'Administrator signed in');
            header('Location: ' . app_url('admin/index.php'));
            exit;
        }
        $attempts['count']++;
        $_SESSION['login_attempts'] = $attempts;
        $error = 'Those sign-in details did not match an active administrator.';
    }
}
require __DIR__ . '/../includes/header.php';
?>
<section class="container py-5"><div class="row justify-content-center"><div class="col-sm-10 col-md-7 col-lg-5"><p class="eyebrow eyebrow-dark">CONTENT MANAGEMENT</p><h1 class="h2 mb-4">Administrator sign in</h1><?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="form-label" for="identity">Username or email</label><input class="form-control mb-3" id="identity" name="identity" autocomplete="username" required><label class="form-label" for="password">Password</label><input class="form-control mb-4" id="password" name="password" type="password" autocomplete="current-password" required><button class="btn btn-success w-100" type="submit">Sign in</button></form><a class="d-inline-block mt-3" href="<?= e(app_url('install.php')) ?>">Initial setup</a></div></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
