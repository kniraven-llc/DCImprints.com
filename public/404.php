<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
http_response_code(404);

$pageTitle = 'Page Not Found | DC Imprints';
$metaDescription = 'The requested page could not be found.';
require APP_ROOT . '/app/layout/header.php';
?>
<section class="py-5">
    <div class="container text-center">
        <h1>Page not found</h1>
        <p class="lead">The requested page does not exist or has moved.</p>
        <a class="btn btn-primary" href="/">Return to the homepage</a>
    </div>
</section>
<?php require APP_ROOT . '/app/layout/footer.php'; ?>
