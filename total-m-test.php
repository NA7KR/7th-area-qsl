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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Cards on Hand";

include_once("$root/config.php");
include("$root/backend/header.php");
include_once("$root/backend/functions.php");

$selectedSection = $_POST['letter']?? null;
$debugMode = isset($_GET['debug']) && $_GET['debug'] === 'true';

// Initialize the arrays:
$paidOperators = [];
$unpaidOperators = [];
$monthlyOperators = [];
$quarterlyOperators = [];
$fullPaymentOperators = [];
$operators = [];

//... (Your code to handle form submissions for filtering, printing, etc.)

if ($selectedSection && isset($config['sections'][$selectedSection])) {
    $dbInfo = $config['sections'][$selectedSection];

    try {
        $pdo = getPDOConnection($dbInfo);
    } catch (RuntimeException $e) {
        die("Database connection failed: ". $e->getMessage());
    }

    $sql = "SELECT
    cr.Call,
    SUM(COALESCE(cr.CardsReceived, 0)) AS CardsReceived,
    (SELECT cm2.CardsMailed FROM tbl_CardM cm2 WHERE cm2.Call = cr.Call ORDER BY cm2.DateMailed DESC LIMIT 1) AS CardsMailed,
    op.`Mail-Inst`
FROM
    tbl_CardRec cr
LEFT JOIN
    tbl_Operator op ON cr.Call = op.Call

GROUP BY cr.Call, op.`Mail-Inst`;";

    try {
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            die("Error preparing statement: ". print_r($pdo->errorInfo(), true));
        }

        $stmt->execute();

        if ($stmt->errorCode()!== '00000') {
            die("Query execution error: ". print_r($stmt->errorInfo(), true));
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process the results:
        foreach ($results as $row) {
            $operators = [
                'Call' => $row['Call'],
                'CardsOnHand' => $row['CardsReceived'],
                'MoneyReceived' => $row['MoneyReceived']?? 0,
                'TotalCost' => $row['Total Cost']?? 0,
                'Balance' => $row['Balance']?? 0,
                'MailInst' => $row['Mail-Inst']
            ];
            $mailInst = $row['Mail-Inst'] ?? 'Full'; // Get Mail-Inst, handling missing key
            // Categorize operators (REPLACE with your actual logic):
            if ($mailInst === 'Monthly') {
                $monthlyOperators[] = $row;
            } elseif ($mailInst === 'Quarterly') {
                $quarterlyOperators[] = $row;
            } // ... other categories
        }

        ksort($paidOperators);  // These should now work
        ksort($unpaidOperators);
        ksort($monthlyOperators);
        ksort($quarterlyOperators);
        ksort($fullPaymentOperators);

        $totalCardsOnHand = array_sum(array_column($operators, 'CardsOnHand'));

    } catch (PDOException $e) {
        die("PDO Exception: ". $e->getMessage());
    }

} // End if ($selectedSection)?>

<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>
    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php foreach ($config['sections'] as $sectionKey => $dbPath):?>
                <option value="<?= htmlspecialchars($sectionKey)?>"
                    <?= $selectedSection === $sectionKey? 'selected': ''?>>
                    <?= htmlspecialchars($sectionKey)?>
                </option>
            <?php endforeach;?>
        </select>
        <button type="submit">Submit</button>
    </form>

    <?php if ($selectedSection):?>
        <h2>Section <?= htmlspecialchars($selectedSection)?></h2>
        <p>Total Cards on Hand: <?= $totalCardsOnHand?? 0?></p>

        <?php if (!empty($monthlyOperators)):?>
            <h3>Monthly</h3>
            <table>
                <thead>
                    <tr>
                        <th>Call</th>
                        <th>Cards On Hand</th>
                        <?php if ($debugMode):?>
                            <th>Money Received</th>
                            <th>Total Cost</th>
                        <?php endif;?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyOperators as $row):?>
                        <tr>
                            <td><?= htmlspecialchars($row['Call'])?></td>
                            <td><?= htmlspecialchars($row['CardsOnHand'] ?? '')?></td>
                            <?php if ($debugMode):?> 
                                <td><?= htmlspecialchars($row['MoneyReceived'])?></td>
                                <td><?= htmlspecialchars($row['TotalCost'])?></td>
                            <?php endif;?>
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        <?php endif;?>

        <?php else:?>
        <p>Please select a section to view data.</p>
    <?php endif;?>
</div>

<?php
include("$root/backend/footer.php");?>