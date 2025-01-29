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
*/

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Cards on Hand";

include_once("$root/config.php");
include("$root/backend/header.php");
include_once("$root/backend/functions.php"); // Include the functions file

// Initialize variables
$selectedSection = null;
$cardsReceivedByCall = [];
$cardsMailedByCall = [];
$cardsReturnedByCall = [];
$moneyReceivedByCall = [];
$mailInstructionByCall = [];
$totalCardsReceived = 0;
$totalCardsMailed = 0;
$totalCardsReturned = 0;
$totalMoneyReceived = 0;
$totalCardsOnHand = 0;
$debugMode = isset($_GET['debug']) && $_GET['debug'] === 'true';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSection = filter_input(INPUT_POST, 'letter', FILTER_DEFAULT) ?? '';
    $selectedSection = strip_tags($selectedSection);
    $selectedSection = htmlspecialchars($selectedSection, ENT_QUOTES, 'UTF-8');

    if ($selectedSection && isset($config['sections'][$selectedSection])) {
        $dbConfig = $config['sections'][$selectedSection] ?? null;

        if ($dbConfig) {
            try {
                $pdo = getPDOConnection($dbConfig);
            } catch (RuntimeException $e) {
                die("Database connection failed: " . $e->getMessage());
            }

            $cardsReceivedByCall = getCallTotals($pdo, 'tbl_CardRec', ['keyName' => 'Call', 'valueName' => 'CardsReceived']);
            $totalCardsReceived = array_sum($cardsReceivedByCall);

            $cardsMailedByCall = getCallTotals($pdo, 'tbl_CardM', ['keyName' => 'Call', 'valueName' => 'CardsMailed']);
            $totalCardsMailed = array_sum($cardsMailedByCall);

            $cardsReturnedByCall = getCallTotals($pdo, 'tbl_CardRet', ['keyName' => 'Call', 'valueName' => 'CardsReturned']);
            $totalCardsReturned = array_sum($cardsReturnedByCall);

            $moneyReceivedByCall = getCallTotals($pdo, 'tbl_MoneyR', ['keyName' => 'Call', 'valueName' => 'MoneyReceived']);
            $totalMoneyReceived = array_sum($moneyReceivedByCall);

            $totalCostByCall = getCallTotals($pdo, 'tbl_CardM', ['keyName' => 'Call', 'valueName' => 'Total Cost']);

            $rawOperatorData = fetchData($pdo, 'tbl_Operator', 'Call, `Mail-Inst`');
            $mailInstructionByCall = [];
            if (!empty($rawOperatorData)) {
                foreach ($rawOperatorData as $row) {
                    $mailInstructionByCall[$row['Call']] = strtolower(trim($row['Mail-Inst'] ?? ''));
                }
            }

            $paidOperators = [];
            $unpaidOperators = [];
            $monthlyOperators = [];
            $quarterlyOperators = [];
            $fullPaymentOperators = [];

            foreach ($cardsReceivedByCall as $call => $cardsReceived) {
                $cardsMailed = $cardsMailedByCall[$call] ?? 0;
                $cardsReturned = $cardsReturnedByCall[$call] ?? 0;
                $cardsOnHand = $cardsReceived - $cardsMailed - $cardsReturned;
                $totalCardsOnHand += $cardsOnHand;

                $moneyReceived = $moneyReceivedByCall[$call] ?? 0;
                $totalCost = $totalCostByCall[$call] ?? 0;
                $balance = $moneyReceived - $totalCost;

                $mailInst = $mailInstructionByCall[$call] ?? 'full';

                $entry = [
                    'Call' => $call,
                    'CardsReceived' => $cardsReceived,
                    'CardsMailed' => $cardsMailed,
                    'CardsReturned' => $cardsReturned,
                    'CardsOnHand' => $cardsOnHand,
                    'MailInst' => $mailInst,
                    'MoneyReceived' => $moneyReceived,
                    'TotalCost' => $totalCost,
                    'Balance' => $balance,
                ];

                if ($cardsOnHand > 0 && ($moneyReceived == 0 || $balance < $config['unpaid_threshold'])) {
                    $unpaidOperators[] = $entry;
                } else {
                    $paidOperators[] = $entry;
                }

                if ($moneyReceived > 0) {
                    $validOptions = ['monthly', 'quarterly', 'full'];
                    if (!in_array($mailInst, $validOptions, true)) {
                        error_log("Unexpected mailing instruction: {$mailInst}. Defaulting to 'full'.");
                        $mailInst = 'full';
                    }

                    switch ($mailInst) {
                        case 'monthly':
                            $monthlyOperators[] = $entry;
                            break;
                        case 'quarterly':
                            $quarterlyOperators[] = $entry;
                            break;
                        default:
                            $fullPaymentOperators[] = $entry;
                            break;
                    }
                } else {
                    $fullPaymentOperators[] = $entry;
                    $unpaidOperators[] = $entry;
                }
            }

            usort($paidOperators, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));
            usort($unpaidOperators, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));

            $monthlyOperators = array_filter($paidOperators, fn($row) => $row['MailInst'] === 'monthly' && $row['CardsOnHand'] > 0);
            $quarterlyOperators = array_filter($paidOperators, fn($row) => $row['MailInst'] === 'quarterly' && $row['CardsOnHand'] > 0);
            $fullPaymentOperators = array_filter($paidOperators, fn($row) => $row['MailInst'] === 'full' && $row['CardsOnHand'] > 0);
        } else {
            echo "Error: Database configuration for the selected section not found.";
        }
    } else {
        echo "Error: Invalid section selected.";
    }
}
?>
<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>
    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php foreach ($config['sections'] as $sectionKey => $dbPath): ?>
                <option value="<?= htmlspecialchars($sectionKey) ?>"
                    <?= $selectedSection === $sectionKey ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sectionKey) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Submit</button>
    </form>

    <?php if ($selectedSection && !empty($cardsReceivedByCall)): ?>
        <h2>Section <?= htmlspecialchars($selectedSection) ?></h2>
        <p>Total Cards on Hand: <?= $totalCardsOnHand ?></p>

        <!-- Monthly Section -->
        <?php if (!empty($monthlyOperators)): ?>
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
                    <?php foreach ($monthlyOperators as $row): ?>
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

        <!-- Quarterly Section -->
        <?php if (!empty($quarterlyOperators)): ?>
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
                    <?php foreach ($quarterlyOperators as $row): ?>
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

        <!-- Full Payment Section -->
        <?php if (!empty($fullPaymentOperators)): ?>
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
                    <?php foreach ($fullPaymentOperators as $row): ?>
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

        <!-- Unpaid Section -->
        <?php if (!empty($unpaidOperators)): ?>
            <h3>Not Paid</h3>
            <table>
                <thead>
                    <tr>
                        <th>Call</th>
                        <th>Cards On Hand</th>
                        <?php if ($debugMode): ?>
                            <th>Money Received</th>
                            <th>Balance</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $seenCallSigns = [];
                    foreach ($unpaidOperators as $row):
                        // Only show each call once
                        if (!in_array($row['Call'], $seenCallSigns)):
                            $seenCallSigns[] = $row['Call'];
                            if ($row['CardsOnHand'] > 0): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Call']) ?></td>
                                    <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                                    <?php if ($debugMode): ?>
                                        <td><?= htmlspecialchars($row['MoneyReceived']) ?></td>
                                        <td style="color: <?= $row['Balance'] < 0 ? 'red' : 'black' ?>;">
                                            <?= ($row['Balance'] < 0 ? '-' : '') . number_format(abs($row['Balance']), 2) ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php elseif ($selectedSection): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>

<?php
include("$root/backend/footer.php");
?>
