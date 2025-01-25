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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$config = include($root . '/config.php');

// We'll return plain text (the balance)
header('Content-Type: text/plain; charset=utf-8');

/**
 * Create a PDO connection using config array: ['host','dbname','username','password'].
 */
function getPDOConnection(array $dbInfo)
{
    try {
        $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['dbname']};charset=utf8";
        $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

$letter = $_POST['letter'] ?? null;
$call   = $_POST['call']   ?? null;

// Check letter
if (!$letter || !isset($config['sections'][$letter])) {
    echo "Invalid letter";
    exit;
}

// Connect
$dbInfo = $config['sections'][$letter];
$pdo = getPDOConnection($dbInfo);

// If we have a call, find the user's balance
if ($call) {
    $stmt = $pdo->prepare("SELECT `AccountBalance` FROM `tbl_Operator` WHERE `Call` = :call");
    $stmt->execute(['call' => $call]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Return just the balance as text, e.g., "12.50" or "0.00"
        // Escape or convert if needed
        echo htmlspecialchars($row['AccountBalance']);
    } else {
        echo "No balance found";
    }
} else {
    echo "Invalid call sign";
}
