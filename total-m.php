<?php
/*
Copyright © 2024 NA7KR Kevin Roberts. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License.
*/

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Cards on Hand";

include_once("$root/config.php");
include("$root/backend/header.php");

// Initialize variables
$selectedLetter = null;
$cardData = [];
$mailedData = [];
$returnedData = [];
$moneyData = [];
$operatorData = [];
$totalCardsReceived = 0;
$totalCardsMailed = 0;
$totalCardsReturned = 0;
$totalMoneyReceived = 0;
$totalCardsOnHand = 0;
$debugMode = isset($_GET['debug']) && $_GET['debug'] === 'true';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //$selectedLetter = filter_input(INPUT_POST, 'letter', FILTER_SANITIZE_STRING);

    // Retrieve the raw input
    $selectedLetter = filter_input(INPUT_POST, 'letter', FILTER_DEFAULT) ?? '';

    // Remove HTML tags
    $selectedLetter = strip_tags($selectedLetter);

    // Optionally encode special characters
    $selectedLetter = htmlspecialchars($selectedLetter, ENT_QUOTES, 'UTF-8');

    // Now $selectedLetter is safe to use in HTML context


    if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
        $dbConfig = $config['sections'][$selectedLetter] ?? null;

        if ($dbConfig) {
            $host = $dbConfig['host'];
            $dbname = $dbConfig['dbname'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }

            function fetchData(PDO $pdo, $tableName, $columns = ['*'], $conditions = null, $params = [])
            {
                try {
                    $columnsList = implode(", ", array_map(fn($col) => "`$col`", $columns));
                    $query = "SELECT $columnsList FROM `$tableName`";

                    if ($conditions) {
                        $query .= " WHERE $conditions";
                    }

                    $stmt = $pdo->prepare($query);
                    $stmt->execute($params);
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    echo "Error: Could not retrieve data from $tableName. " . $e->getMessage();
                    return [];
                }
            }

            function processData($rawData, $keyIndexes)
            {
                $data = [];
                foreach ($rawData as $row) {
                    $key = $row[$keyIndexes['keyName']];
                    $value = (float)$row[$keyIndexes['valueName']]; // Ensure Total Cost is treated as a float

                    if (isset($data[$key])) {
                        $data[$key] += $value; // Accumulate Total Cost for the same Call
                    } else {
                        $data[$key] = $value; // Initialize Total Cost
                    }
                }
                return $data;
            }

            function getData(PDO $pdo, $tableName, $keyIndexes)
            {
                $rawData = fetchData($pdo, $tableName, [$keyIndexes['keyName'], $keyIndexes['valueName']]);
                if (!empty($rawData)) {
                    return processData($rawData, $keyIndexes);
                }
                return [];
            }

            $cardData = getData($pdo, 'tbl_CardRec', ['keyName' => 'Call', 'valueName' => 'CardsReceived']);
            $totalCardsReceived = array_sum($cardData);

            $mailedData = getData($pdo, 'tbl_CardM', ['keyName' => 'Call', 'valueName' => 'CardsMailed']);
            $totalCardsMailed = array_sum($mailedData);

            $returnedData = getData($pdo, 'tbl_CardRet', ['keyName' => 'Call', 'valueName' => 'CardsReturned']);
            $totalCardsReturned = array_sum($returnedData);

            $moneyData = getData($pdo, 'tbl_MoneyR', ['keyName' => 'Call', 'valueName' => 'MoneyReceived']);
            $totalMoneyReceived = array_sum($moneyData);

            $totalCostData = getData($pdo, 'tbl_CardM', ['keyName' => 'Call', 'valueName' => 'Total Cost']);


            $rawOperatorData = fetchData($pdo, 'tbl_Operator', ['Call', 'Mail-Inst']);
            if (!empty($rawOperatorData)) {
                foreach ($rawOperatorData as $row) {
                    //$operatorData[$row['Call']] = strtolower(trim($row['Mail-Inst']));
                    $operatorData[$row['Call']] = strtolower(trim($row['Mail-Inst'] ?? ''));
                }
            }

            $greenData    = [];
            $redData      = [];
            $monthlyData  = [];
            $quarterlyData= [];
            $fullData     = [];
            foreach ($cardData as $call => $cardsReceived) {
                $cardsMailed = $mailedData[$call] ?? 0;
                $cardsReturned = $returnedData[$call] ?? 0;
                $cardsOnHand = $cardsReceived - $cardsMailed - $cardsReturned;
                $totalCardsOnHand += $cardsOnHand;
                $moneyReceived = $moneyData[$call] ?? 0;
                $totalCost = $totalCostData[$call] ?? 0; // Correctly associate Total Cost with Call
                $balance = $moneyReceived - $totalCost; // Calculate balance (positive or negative)
                $mailInst = $operatorData[$call] ?? 'full';

                $entry = [
                    'Call' => $call,
                    'CardsReceived' => $cardsReceived,
                    'CardsMailed' => $cardsMailed,
                    'CardsReturned' => $cardsReturned,
                    'CardsOnHand' => $cardsOnHand,
                    'MailInst' => $mailInst,
                    'MoneyReceived' => $moneyReceived,
                    'TotalCost' => $totalCost,
                    'Balance' => $balance // Include balance for clarity
                ];

                if ($cardsOnHand > 0 && ($moneyReceived == 0 || $balance < $config['unpaid_threshold'])) {
                    $redData[] = $entry; // Unpaid entries
                } else {
                    $greenData[] = $entry; // Paid entries
                }
                if ($moneyReceived > 0) {
                    // Validate and normalize $mailInst
                    $validMailInst = ['monthly', 'quarterly', 'full'];
                    if (!in_array($mailInst, $validMailInst, true)) {
                        echo "$call + $mailInst <br>";
                        error_log("Unexpected mailInst value: $mailInst. Defaulting to 'full'.");
                        $mailInst = 'full'; // Default to 'full' for invalid or unexpected values
                    }

                    if (!in_array($mailInst, $validMailInst, true)) {
                        echo "$call ";
                        error_log("Unexpected mailInst value: $mailInst. Defaulting to 'full'.");
                        $mailInst = 'full'; // Default to 'full' for invalid or unexpected values
                    }
                
                    // Categorize based on normalized $mailInst
                    if ($mailInst === 'monthly') {
                        $monthlyData[] = $entry;
                    } elseif ($mailInst === 'quarterly') {
                        $quarterlyData[] = $entry;
                    } elseif ($mailInst === 'full') {
                        $fullData[] = $entry;
                    }
                    //echo "$call + $mailInst EXIT<br>";
                }
                   
                else 
                {
                    $mailInst = 'full';
                    // If not equal to 'monthly', 'quarterly', or 'full', categorize as 'full'
                    $fullData[] = $entry;
                    $redData[] = $entry; // Unpaid entries
                }
            }
           
            usort($greenData, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));
            usort($redData, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));
           
            $monthlyData = array_filter($greenData, fn($entry) => $entry['MailInst'] === 'monthly');
            $quarterlyData = array_filter($greenData, fn($entry) => $entry['MailInst'] === 'quarterly');
            $fullData = array_filter($greenData, fn($entry) => $entry['MailInst'] === 'full');
        } else {
            echo "Error: Database configuration for the selected section not found.";
        }
    } else {
        echo "Error: Invalid letter selected.";
    }
}
?>
<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>
    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php foreach ($config['sections'] as $letter => $dbPath): ?>
                <option value="<?= htmlspecialchars($letter) ?>" <?= $selectedLetter === $letter ? 'selected' : '' ?>>
                    <?= htmlspecialchars($letter) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Submit</button>
    </form>

    <?php if ($selectedLetter && !empty($cardData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Total Cards on Hand: <?= $totalCardsOnHand ?></p>

        <?php if (!empty($monthlyData)): ?>
            <?php $filteredMonthly = array_filter($monthlyData, fn($row) => $row['CardsOnHand'] > 0); ?>
            <?php if (!empty($filteredMonthly)): ?>
                <h3>Monthly</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Call</th>
                            <th>Cards On Hand</th>
                            <?php if ($debugMode): ?>
                                <th>Money Received</th>
                                <th>Total Cost</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredMonthly as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Call']) ?></td>
                                <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                                <?php if ($debugMode): ?>
                                    <td><?= htmlspecialchars($row['MoneyReceived']) ?></td> 
                                    <td><?= htmlspecialchars($row['TotalCost']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($quarterlyData)): ?>
            <?php $filteredQuarterly = array_filter($quarterlyData, fn($row) => $row['CardsOnHand'] > 0); ?>
            <?php if (!empty($filteredQuarterly)): ?>
                <h3>Quarterly</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Call</th>
                            <th>Cards On Hand</th>
                            <?php if ($debugMode): ?>
                                <th>Money Received</th> 
                                <th>Total Cost</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredQuarterly as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Call']) ?></td>
                                <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                                <?php if ($debugMode): ?>
                                    <td><?= htmlspecialchars($row['MoneyReceived']) ?></td>
                                    <td><?= htmlspecialchars($row['TotalCost']) ?></td> 
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($fullData)): ?>
            <?php $filteredFull = array_filter($fullData, fn($row) => $row['CardsOnHand'] > 0); ?>
            <?php if (!empty($filteredFull)): ?>
                <h3>Full</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Call</th>
                            <th>Cards On Hand</th>
                            <?php if ($debugMode): ?>
                                <th>Money Received</th>
                                <th>Total Cost</th> 
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filteredFull as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Call']) ?></td>
                                <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                                <?php if ($debugMode): ?>
                                    <td><?= htmlspecialchars($row['MoneyReceived']) ?></td> 
                                    <td><?= htmlspecialchars($row['TotalCost']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($redData)): ?>
            <h3>Not Paid</h3>
            <table>
                <thead>
                    <tr>
                        <th>Call</th>
                        <th>Cards On Hand</th>
                        <?php if ($debugMode): ?>
                            <th>Money Received</th> 
                            <th>Total Cost</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $seenCalls = [];
                    foreach ($redData as $row):
                        if (!in_array($row['Call'], $seenCalls)):
                            $seenCalls[] = $row['Call'];
                            if ($row['CardsOnHand'] > 0): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Call']) ?></td>
                                <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                                <?php if ($debugMode): ?>
                                    <td><?= htmlspecialchars($row['MoneyReceived']) ?></td> 
                                    <td style="color: <?= $row['Balance'] < 0 ? 'red' : 'black' ?>;">
                                    <?= $row['Balance'] < 0 ? '-' : '' ?><?= number_format(abs($row['Balance']), 2) ?>
                                </td>
                                     
                                <?php endif; ?>
                            </tr>
                        <?php endif; ?>
                    <?php endif; endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php elseif ($selectedLetter): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>


<?php
include("$root/backend/footer.php");
?>
