<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';

if (admin_authenticated()) {
    redirect('/admin/');
}

$error = flash('error');

if (is_post()) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (attempt_admin_login(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
        redirect('/admin/');
    } else {
        $error = 'The username or password was not accepted.';
    }
}

$pageTitle = 'Admin Sign In | DC Imprints';
require APP_ROOT . '/app/layout/admin-header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4">Admin sign in</h1>
                <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" id="username" name="username" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Sign in</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/app/layout/admin-footer.php'; ?>
