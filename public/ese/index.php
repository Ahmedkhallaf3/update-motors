 <?php
$files = array_filter(scandir(__DIR__), function($file) {
    return is_file($file) && $file !== basename(__FILE__);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bootstrap Nav Files</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Files</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarFiles" aria-controls="navbarFiles" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarFiles">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach($files as $file): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($file); ?>">
                            <?php echo htmlspecialchars($file); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Bootstrap 5 JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
