<?php
/*
Copyright © 2024 NA7KR Kevin Roberts. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
    'User'   => $config['user']['submenu'] ?? [],
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