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
$root = realpath($_SERVER["DOCUMENT_ROOT"]);

// Include config.php
$config = include($root . '/config.php');
$pages = $config['pages'];
$admin = $config['admin'];

include 'functions.php';

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true; // Set this based on your login logic

// Filter submenu items based on login status
$submenu = array_filter($admin['submenu'], function($item) use ($isLoggedIn) {
    return $item['login'] === $isLoggedIn;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="/styles.css"> <!-- Update the path accordingly -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
            <?php $role = htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?>
            <?php
            //var_dump($role); // <-- Add this line

            //var_dump($submenu); // <-- Add this line
            
      

            $currentSubmenu = [];

            if (isset($_SESSION['role'])) {
                if ($role === 'Admin') {
                    $currentSubmenu = $config['admin']['submenu'] ?? [];
                } elseif ($role === 'Ops') {
                    $currentSubmenu = $config['ops']['submenu'] ?? [];
                }
            }
            
          
            ?>
            
            <a href="<?php echo $role; ?>"><?php echo $role; ?></a>
            <div class="dropdown-content">
                <?php foreach ($currentSubmenu as $subTitle => $subItem): ?>
                    <?php
                    $showMenuItem = true;
            
                    if (isset($subItem['login'])) {
                        if ($subItem['login'] === true && !isset($_SESSION['loggedin'])) {
                            $showMenuItem = false;
                        } elseif ($subItem['login'] === false && isset($_SESSION['loggedin'])) {
                            $showMenuItem = false;
                        }
                    }
                    ?>
            
                    <?php if ($showMenuItem): ?>
                        <a href="<?= htmlspecialchars($subItem['url']) ?>"><?= htmlspecialchars($subTitle) ?></a>
                    <?php endif; ?>
            
                <?php endforeach; ?>
            </div>
    </nav>
<?php

