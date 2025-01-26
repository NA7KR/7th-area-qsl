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
        die("Database connection failed: " . htmlspecialchars($e->getMessage()));
    }
}

// We'll return HTML <option> tags
header('Content-Type: text/html; charset=utf-8');

$selectedLetter = $_POST['letter'] ?? null; // must match the 'letter=' in the fetch body
$call           = $_POST['call']   ?? null;

// Validate letter
if (!$selectedLetter || !isset($config['sections'][$selectedLetter])) {
    echo '<option>Invalid or missing letter in request</option>';
    exit;
}

// Create PDO connection
$dbInfo = $config['sections'][$selectedLetter];
$pdo = getPDOConnection($dbInfo);

if ($call) {
    $stmt = $pdo->prepare("SELECT `Status` FROM `tbl_Operator` WHERE `Call` = :call");
    $stmt->execute(['call' => $call]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach ($results as $row) {
            // Escape the status to prevent any HTML injection
            $status = htmlspecialchars($row['Status']);
            echo htmlspecialchars($status); // Plain text output
        }
    } else {
        echo "No status found.";
    }
} else {
    echo "No status found.";
}
