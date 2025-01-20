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
 session_start();

 
 $root = realpath($_SERVER["DOCUMENT_ROOT"]);
 $title = "Users to Pay Page";
 $config = include($root . '/config.php');
 
 include("$root/backend/header.php");
 
 if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
     header('Location: login.php');
     exit;
 }
 
 ini_set('error_reporting', E_ALL);
 ini_set('display_errors', '1');
 
 // Connect to the database
 function getPDOConnection($dbConfig) {
     try {
         $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8", $dbConfig['username'], $dbConfig['password']);
         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         return $pdo;
     } catch (PDOException $e) {
         die("Database connection failed: " . $e->getMessage());
     }
 }
 
 // Fetch data efficiently
 function fetchData($pdo, $tableName, $columns = '*') {
     $escapedColumns = array_map(function ($col) {
         return strpos($col, '`') !== false ? $col : "`$col`";
     }, explode(',', $columns));
     $columnsString = implode(',', $escapedColumns);
 
     $query = "SELECT $columnsString FROM `$tableName`";
     try {
         $stmt = $pdo->query($query);
         $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
         if (empty($data)) {
             error_log("No data found in table `$tableName`.");
         }
 
         return $data;
     } catch (PDOException $e) {
         die("Error fetching data from `$tableName`: " . $e->getMessage());
     }
 }
 
 // Normalize headers
 function normalizeHeaders($headers) {
     return array_map('trim', $headers);
 }
 
 // Sanitize email
 function sanitizeEmail($email) {
     return preg_replace('/#mailto:[^#]+#/', '', $email);
 }
 
 // Initialize variables
 $selectedLetter = null;
 $filterEmail = false;
 $filterNoEmail = false;
 $cardData = [];
 $mailedData = [];
 $returnedData = [];
 $moneyReceivedData = [];
 $postalCostData = [];
 $otherCostData = [];
 $operatorData = [];
 $totalCardsReceived = 0;
 $totalCardsMailed = 0;
 $totalCardsReturned = 0;
 $totalCardsOnHand = 0;
 $totalPaid = 0;
 $totalMoneyReceived = 0;
 $totalPostalCost = 0;
 $totalOtherCost = 0;
 $totalTotal = 0;
 
 if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
     $selectedLetter = $_POST['letter'];
     $filterEmail = isset($_POST['filter_email']);
     $filterNoEmail = isset($_POST['filter_no_email']);
 
     if (isset($config['sections'][$selectedLetter])) {
         $dbConfig = $config['sections'][$selectedLetter];
         $pdo = getPDOConnection($dbConfig);
 
         // Fetch Card Data
         $rawCardData = fetchData($pdo, 'tbl_CardRec', '`Call`,`CardsReceived`');
         foreach ($rawCardData as $row) {
             $call = $row['Call'] ?? null;
             $cardsReceived = (int)($row['CardsReceived'] ?? 0);
             if ($call) {
                 $cardData[$call] = ($cardData[$call] ?? 0) + $cardsReceived;
                 $totalCardsReceived += $cardsReceived;
             }
         }
 
         // Fetch Mailed Data
         $rawMailedData = fetchData($pdo, 'tbl_CardM', '`Call`,`CardsMailed`,`Postal Cost`,`Other Cost`');
         foreach ($rawMailedData as $row) {
             $call = $row['Call'] ?? null;
             $cardsMailed = (int)($row['CardsMailed'] ?? 0);
             $postalCost = (float)($row['Postal Cost'] ?? 0.0);
             $otherCost = (float)($row['Other Cost'] ?? 0.0);
             if ($call) {
                 $mailedData[$call] = ($mailedData[$call] ?? 0) + $cardsMailed;
                 $postalCostData[$call] = ($postalCostData[$call] ?? 0.0) + $postalCost;
                 $otherCostData[$call] = ($otherCostData[$call] ?? 0.0) + $otherCost;
                 $totalCardsMailed += $cardsMailed;
             }
         }
 
         // Fetch Returned Data
         $rawReturnedData = fetchData($pdo, 'tbl_CardRet', '`Call`,`CardsReturned`');
         foreach ($rawReturnedData as $row) {
             $call = $row['Call'] ?? null;
             $cardsReturned = (int)($row['CardsReturned'] ?? 0);
             if ($call) {
                 $returnedData[$call] = ($returnedData[$call] ?? 0) + $cardsReturned;
                 $totalCardsReturned += $cardsReturned;
             }
         }
 
         // Fetch Money Received Data
         $rawMoneyReceivedData = fetchData($pdo, 'tbl_MoneyR', '`Call`,`MoneyReceived`');
         foreach ($rawMoneyReceivedData as $row) {
             $call = $row['Call'] ?? null;
             $moneyReceived = (float)($row['MoneyReceived'] ?? 0.0);
             if ($call) {
                 $moneyReceivedData[$call] = ($moneyReceivedData[$call] ?? 0.0) + $moneyReceived;
                 $totalPaid += $moneyReceived;
             }
         }
 
         // Fetch Operator Data
         $rawOperatorData = fetchData($pdo, 'tbl_Operator', '`Call`,`FirstName`,`LastName`,`Mail-Inst`,`E-Mail`,`Address_1`,`City`,`State`,`Zip`');
         foreach ($rawOperatorData as $row) {
             $call = $row['Call'] ?? null;
             if ($call) {
                 $operatorData[$call] = [
                     'firstName' => $row['FirstName'] ?? '',
                     'lastName' => $row['LastName'] ?? '',
                     'mailInst' => strtolower(trim($row['Mail-Inst'] ?? '')),
                     'email' => sanitizeEmail($row['E-Mail'] ?? ''),
                     'address' => $row['Address_1'] ?? '',
                     'city' => $row['City'] ?? '',
                     'state' => $row['State'] ?? '',
                     'zip' => $row['Zip'] ?? '',
                     'moneyReceived' => $moneyReceivedData[$call] ?? 0,
                     'postalCost' => $postalCostData[$call] ?? 0,
                     'otherCost' => $otherCostData[$call] ?? 0
                 ];
             }
         }
 
         // Process Data
         $redData = [];
         foreach ($operatorData as $call => $data) {
             $cardsReceived = $cardData[$call] ?? 0;
             $cardsMailed = $mailedData[$call] ?? 0;
             $cardsReturned = $returnedData[$call] ?? 0;
             $cardsOnHand = $cardsReceived - $cardsMailed - $cardsReturned;
             $totalCardsOnHand += $cardsOnHand;
 
             $totalCost = $data['postalCost'] + $data['otherCost'];
             $total = $data['moneyReceived'] - $totalCost;
 
             if ($data['moneyReceived'] > 0 && ((!$filterEmail && !$filterNoEmail) ||
                 ($filterEmail && !empty($data['email'])) ||
                 ($filterNoEmail && empty($data['email'])))) {
                 $totalMoneyReceived += $data['moneyReceived'];
                 $totalPostalCost += $data['postalCost'];
                 $totalOtherCost += $data['otherCost'];
                 $totalTotal += $total;
                 $entry = [
                     'Call' => $call,
                     'FirstName' => $data['firstName'],
                     'LastName' => $data['lastName'],
                     'CardsOnHand' => $cardsOnHand,
                     'MoneyReceived' => $data['moneyReceived'],
                     'TotalCost' => $totalCost,
                     'Total' => $total,
                     'MailInst' => $data['mailInst'],
                     'Email' => $data['email'],
                     'Address' => $data['address'],
                     'City' => $data['city'],
                     'State' => $data['state'],
                     'Zip' => $data['zip']
                 ];
                 $redData[] = $entry;
             }
         }
 
         usort($redData, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));
     } else {
         echo "Error: Invalid database configuration.";
     }
 }
 
 include("$root/backend/footer.php");
 ?>
 



<div class="center-content">
    <style>
        .center-content form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .center-content form label {
            margin-bottom: 10px;
        }
        .center-content form input,
        .center-content form select,
        .center-content form button {
            margin-bottom: 20px;
        }
    </style>

    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>
    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php if (isset($config['sections']) && is_array($config['sections'])): ?>
                <?php foreach ($config['sections'] as $letter => $dbPath): ?>
                    <option value="<?= htmlspecialchars($letter) ?>" <?= $selectedLetter === $letter ? 'selected' : '' ?>>
                        <?= htmlspecialchars($letter) ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">No sections available</option>
            <?php endif; ?>
        </select>
        <br><br>

        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($redData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Cards On Hand</th>
                    <th>Money Received</th>
                    <th>Shipping Cost</th>
                    <th>Total Available</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Zip</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($redData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Call']) ?></td>
                        <td><?= htmlspecialchars($row['FirstName']) ?></td>
                        <td><?= htmlspecialchars($row['LastName']) ?></td>
                        <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                        <td><?= htmlspecialchars($row['MoneyReceived']) ?></td>
                        <td><?= htmlspecialchars($row['TotalCost']) ?></td>
                        <td><?= number_format($row['Total'], 2) ?></td>
                        <td><?= htmlspecialchars($row['Email']) ?></td>
                        <td><?= htmlspecialchars($row['Address']) ?></td>
                        <td><?= htmlspecialchars($row['City']) ?></td>
                        <td><?= htmlspecialchars($row['State']) ?></td>
                        <td><?= htmlspecialchars($row['Zip']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4"><strong>Totals</strong></td>
                    <td><strong><?= htmlspecialchars($totalMoneyReceived) ?></strong></td>
                    <td><strong><?= htmlspecialchars($totalPostalCost + $totalOtherCost) ?></strong></td>
                    <td><strong><?= htmlspecialchars($totalTotal) ?></strong></td>
                    <td colspan="5"></td>
                </tr>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>

<?php
include("$root/backend/footer.php");
?>
