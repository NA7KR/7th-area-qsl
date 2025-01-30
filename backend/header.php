<?php
/* ... (License and includes) */

session_start();

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$config = include($root . '/config.php');
$pages = $config['pages'];

include 'functions.php'; // If you have any functions there

$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$role = $_SESSION['role'] ?? 'Admin';

// Submenus (from config.php)
$submenus = [
    'Admin' => $config['admin']['submenu'] ?? [],
    'Ops'   => $config['ops']['submenu'] ?? [],
];

$currentSubmenu = $submenus[$role] ?? $submenus['Admin'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title> <link rel="stylesheet" href="/styles.css"> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
</head>
<body>
    <nav class="menu">
        <div class="menu-toggle" id="menu-toggle">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
        <div class="menu-links" id="menu-links">
            <?php foreach ($pages as $title => $url): ?>
                <a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($title) ?></a>
            <?php endforeach; ?>
            <div class="dropdown">
                <a href="#"><?php echo htmlspecialchars($role); ?></a>
                <div class="dropdown-content">
                    <?php foreach ($currentSubmenu as $subTitle => $subItem): ?>
                        <?php
                        $showMenuItem = true;

                        if (isset($subItem['login'])) {
                            if ($subItem['login'] === true && !$isLoggedIn) {
                                $showMenuItem = false;
                            } elseif ($subItem['login'] === false && $isLoggedIn) {
                                $showMenuItem = false;
                            }
                        }
                        ?>

                        <?php if ($showMenuItem): ?>
                            <a href="<?= htmlspecialchars($subItem['url'] ?? '') ?>"><?= htmlspecialchars($subTitle ?? '') ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </nav>
    <script src="script.js"></script> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>