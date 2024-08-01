<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
// Include the configuration file
$config = include('config.php');

// Check if the 'admin' key exists in the config array
if (!isset($config['admin']) || !isset($config['admin']['submenu'])) {
    die("Error: 'admin' or 'submenu' key not found in configuration.");
}

// Extract submenu items from the configuration
$submenuItems = $config['admin']['submenu'];

// Filter items where login status is true
$submenuTrue = array_filter($submenuItems, function($item) {
    return $item['login'] === true;
});

// Filter items where login status is false
$submenuFalse = array_filter($submenuItems, function($item) {
    return $item['login'] === false;
});

// Display items with login status true
echo "<h3>Items for Logged In Users:</h3>";
foreach ($submenuTrue as $name => $item) {
    echo $name . " - " . $item['url'] . "<br>";
}

// Display items with login status false
echo "<h3>Items for Logged Out Users:</h3>";
foreach ($submenuFalse as $name => $item) {
    echo $name . " - " . $item['url'] . "<br>";
}
?>

