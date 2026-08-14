<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'DC Imprints Admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<header class="navbar bg-dark navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="/admin/">DC Imprints Admin</a>
        <?php if (admin_authenticated()): ?>
            <form method="post" action="/admin/" class="mb-0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="logout">
                <button class="btn btn-outline-light btn-sm" type="submit">Sign out</button>
            </form>
        <?php endif; ?>
    </div>
</header>
<main class="container py-4">
