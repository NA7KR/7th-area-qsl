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
$config = include($root . '/config.php');
include 'functions.php';
// Return raw HTML <option> tags
header('Content-Type: text/html; charset=utf-8');


$letter = $_POST['letter'] ?? null;
$call   = $_POST['call']   ?? null;

if (!$letter || !isset($config['sections'][$letter])) {
    echo '<option>Invalid or missing letter in request</option>';
    exit;
}

// Create PDO connection
$dbInfo = $config['sections'][$letter];
$pdo = getPDOConnection($dbInfo);

if ($call) {
    // Suppose we store mail instructions in "tbl_Operator" or another table.
    // Let's assume there's a column "MailInst" in "tbl_Operator" or "tbl_Recipient", etc.
    // For example:
    $stmt = $pdo->prepare("SELECT `Mail-Inst` FROM `tbl_Operator` WHERE `Call` = :call");
    $stmt->execute(['call' => $call]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach ($results as $row) {
            $mailInst = htmlspecialchars($row['Mail-Inst']);
            echo htmlspecialchars($mailInst); // Plain text output
        }
    } else {
        echo "No  found";
    }
} else {
    echo "No  found";
}
