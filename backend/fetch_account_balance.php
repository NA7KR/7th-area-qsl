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

 session_start();
// Get the document root and include the configuration file
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$config = include($root . '/config.php');
include('functions.php');
// We'll return plain text (the balance)
header('Content-Type: text/plain; charset=utf-8');

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