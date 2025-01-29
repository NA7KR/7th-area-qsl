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
//print_r($_POST);
session_start();


error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Stamp Tracker";

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
include 'functions.php';

// ----------------- MAIN -----------------
$selectedLetter = null;
$call = null;
$CardsToMail = null;
$weight = null;
$PostageCost = null;
$OtherCost = null;
$TotalCost = null;
$ID = null;
$dataRows = [];
$aggregatedStamps = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedLetter = $_POST['letter'] ?? null;
    $call = $_POST['Call'] ?? null;
    $CardsToMail = $_POST['CardsToMail'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $PostageCost = $_POST['PostageCost'] ?? null;
    $OtherCost = $_POST['OtherCost'] ?? null;
    $TotalCost = $_POST['TotalCost'] ?? null;
    $ID = $_POST['ID'] ?? null;
    if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
        $dbInfo = $config['sections'][$selectedLetter];
        $pdo = getPDOConnection($dbInfo);

        $dataRows = fetchAllStamps($pdo, 'tbl_Stamps');

        $aggregatedStamps = parseAndAggregate($dataRows, $config);
        usort($aggregatedStamps, function ($a, $b) {
            return strcasecmp($a['Value'], $b['Value']);
        });
    } else {
        echo "Error: Invalid or missing section configuration.";
    }
}
?>

<div class="center-content">
    <h1 class="my-4 text-center">7th Area QSL Bureau - Stamp Tracker</h1>

    <form method="POST">
        <button type="submit">Submit</button>
    </form>

    <h2>Stamp Summary</h2>
    <?php echo "Call: $call"; ?><br>
    <?php echo "CardsToMail: $CardsToMail"; ?><br>
    <?php echo "weight: $weight"; ?><br>
    <?php echo "PostageCost: $PostageCost"; ?><br>
    <?php echo "OtherCost: OtherCost"; ?><br>
    <?php echo "TotalCost: $TotalCost"; ?><br>
    <?php echo "ID: $ID"; ?><br>
    <?php if (!empty($aggregatedStamps)): ?>
        <table>
            <thead>
                <tr>
                    <th>Value Of Stamps</th>
                    <th class="stamps-on-hand">Stamps On Hand</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalOnHand = 0;

                foreach ($aggregatedStamps as $row): 
                    $totalOnHand += $row['Stamps On Hand'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Value'] ?? '') ?></td>
                        <td class="stamps-on-hand"><?= (int)$row['Stamps On Hand'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>
