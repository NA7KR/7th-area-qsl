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

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the document root and include the configuration file
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$config = include($root . '/config.php');

// We'll return plain text (the balance)
header('Content-Type: text/plain; charset=utf-8');

/**
 * Create a PDO connection using config array: ['host','dbname','username','password'].
 * 
 * @param array $dbInfo Database connection information
 * @return PDO The PDO connection object
 */
function getPDOConnection(array $dbInfo)
{
    try {
        // Create the DSN string for the PDO connection
        $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['dbname']};charset=utf8";
        
        // Create the PDO connection
        $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
        
        // Set the PDO error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Return the PDO connection object
        return $pdo;
    } catch (PDOException $e) {
        // If there is an error, output the error message and exit
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Fetch mailed data for a single call.
 * 
 * @param PDO $pdo The PDO connection object
 * @param string $callSign The call sign to fetch data for
 * @return array The fetched data as an associative array
 */
function fetchMailedData(PDO $pdo, string $callSign): array
{
    // SQL query to fetch mailed data for the given call sign
    $sql = "SELECT `Call`, `CardsMailed`, `Postal Cost`, `Other Cost`
            FROM `tbl_CardM`
            WHERE `Call` = :call";

    // Log the SQL query and parameter for debugging
    error_log("[DEBUG] SQL => $sql, param => $callSign");

    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':call', $callSign, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch and return the result as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch money received data for a single call.
 * 
 * @param PDO $pdo The PDO connection object
 * @param string $callSign The call sign to fetch data for
 * @return array The fetched data as an associative array
 */
function fetchMoneyReceived(PDO $pdo, string $callSign): array
{
    // SQL query to fetch money received data for the given call sign
    $sql = "SELECT `Call`, `MoneyReceived`
            FROM `tbl_MoneyR`
            WHERE `Call` = :call";

    // Log the SQL query and parameter for debugging
    error_log("[DEBUG] SQL => $sql, param => $callSign");

    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':call', $callSign, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch and return the result as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --------------- Main Script ---------------

// Get the letter and call sign from POST request
$letter = $_POST['letter'] ?? null;
$call   = $_POST['call']   ?? null;

// Convert letter/call to uppercase
if ($letter) $letter = strtoupper($letter);
if ($call)   $call   = strtoupper($call);

// Validate letter
if (!$letter || !isset($config['sections'][$letter])) {
    // If the letter is invalid, output an error message and exit
    echo "Invalid letter";
    exit;
}

// Connect to the database using the configuration for the given letter
$dbInfo = $config['sections'][$letter];
$pdo = getPDOConnection($dbInfo);

// If we have a call, find the user's balance
if ($call) {
    // Arrays for storing totals per call
    $postalCostData     = [];
    $otherCostData      = [];
    $moneyReceivedData  = [];

    // Fetch mailed data for the given call sign
    $rawMailedData = fetchMailedData($pdo, $call);
    foreach ($rawMailedData as $row) {
        // We'll avoid overwriting the `$call` from $_POST
        // by naming this `$rowCall` instead.
        $rowCall     = $row['Call'] ?? null;
        $postalCost  = (float)($row['Postal Cost'] ?? 0.0);
        $otherCost   = (float)($row['Other Cost']  ?? 0.0);

        // Accumulate postal and other costs for each call sign
        if ($rowCall) {
            $postalCostData[$rowCall] = ($postalCostData[$rowCall] ?? 0) + $postalCost;
            $otherCostData[$rowCall]  = ($otherCostData[$rowCall]  ?? 0) + $otherCost;
        }
    }

    // Fetch money received data for the given call sign
    $rawMoneyData = fetchMoneyReceived($pdo, $call);
    foreach ($rawMoneyData as $row) {
        $rowCall       = $row['Call'] ?? null;
        $moneyReceived = (float)($row['MoneyReceived'] ?? 0.0);

        // Accumulate money received for each call sign
        if ($rowCall) {
            $moneyReceivedData[$rowCall] = ($moneyReceivedData[$rowCall] ?? 0.0) + $moneyReceived;
        }
    }

    // Now compute the totals for the user’s requested call
    $postalCost = $postalCostData[$call] ?? 0.0;
    $otherCost  = $otherCostData[$call]  ?? 0.0;
    $totalCost  = $postalCost + $otherCost;

    $amountReceived = $moneyReceivedData[$call] ?? 0.0;
    $balance        = $amountReceived - $totalCost;

    // Format to always have two decimals:
    $formattedBalance = number_format($balance, 2);

    // Output the formatted balance
    echo htmlspecialchars($formattedBalance);

} else {
    // If the call sign is invalid, output an error message
    echo "Invalid call sign";
}
?>