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

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Users to Pay Page";

include("$root/backend/header.php");

// If not logged in, redirect
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

/** 
 * Sanitize email by removing '#mailto:...#' patterns.
 */
function sanitizeEmail($email) {
    return preg_replace('/#mailto:[^#]+#/', '', $email);
}

// -----------------------------------------
// Initialize variables
// -----------------------------------------
$selectedLetter     = null;
$filterEmail        = false;
$filterNoEmail      = false;

$cardData           = [];
$mailedData         = [];
$returnedData       = [];
$moneyReceivedData  = [];
$postalCostData     = [];
$otherCostData      = [];
$operatorData       = [];

$totalCardsReceived = 0;
$totalCardsMailed   = 0;
$totalCardsReturned = 0;
$totalCardsOnHand   = 0;
$totalPaid          = 0;
$totalMoneyReceived = 0;
$totalPostalCost    = 0;
$totalOtherCost     = 0;
$totalTotal         = 0;

// This array will hold final rows for display
$redData = [];

// -----------------------------------------
// Handle form submission
// -----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    $filterEmail    = isset($_POST['filter_email']);
    $filterNoEmail  = isset($_POST['filter_no_email']);

    // Ensure letter is valid in config
    if (isset($config['sections'][$selectedLetter])) {
        // Build PDO from config
        $dbConfig = $config['sections'][$selectedLetter];
        $pdo = getPDOConnection($dbConfig);

        // 1) Fetch Card Data (tbl_CardRec)
        $rawCardData = fetchData($pdo, 'tbl_CardRec', '`Call`,`CardsReceived`');
        foreach ($rawCardData as $row) {
            $call           = $row['Call'] ?? null;
            $cardsReceived  = (int)($row['CardsReceived'] ?? 0);
            if ($call) {
                $cardData[$call] = ($cardData[$call] ?? 0) + $cardsReceived;
                $totalCardsReceived += $cardsReceived;
            }
        }

        // 2) Fetch Mailed Data (tbl_CardM)
        $rawMailedData = fetchData($pdo, 'tbl_CardM', '`Call`,`CardsMailed`,`Postal Cost`,`Other Cost`');
        foreach ($rawMailedData as $row) {
            $call        = $row['Call']          ?? null;
            $cardsMailed = (int)($row['CardsMailed']  ?? 0);
            $postalCost  = (float)($row['Postal Cost'] ?? 0.0);
            $otherCost   = (float)($row['Other Cost']  ?? 0.0);

            if ($call) {
                $mailedData[$call]      = ($mailedData[$call]      ?? 0) + $cardsMailed;
                $postalCostData[$call]  = ($postalCostData[$call]  ?? 0) + $postalCost;
                $otherCostData[$call]   = ($otherCostData[$call]   ?? 0) + $otherCost;
                $totalCardsMailed       += $cardsMailed;
            }
        }

        // 3) Fetch Returned Data (tbl_CardRet)
        $rawReturnedData = fetchData($pdo, 'tbl_CardRet', '`Call`,`CardsReturned`');
        foreach ($rawReturnedData as $row) {
            $call          = $row['Call'] ?? null;
            $cardsReturned = (int)($row['CardsReturned'] ?? 0);
            if ($call) {
                $returnedData[$call] = ($returnedData[$call] ?? 0) + $cardsReturned;
                $totalCardsReturned += $cardsReturned;
            }
        }

        // 4) Fetch Money Received Data (tbl_MoneyR)
        $rawMoneyReceivedData = fetchData($pdo, 'tbl_MoneyR', '`Call`,`MoneyReceived`');
        foreach ($rawMoneyReceivedData as $row) {
            $call          = $row['Call'] ?? null;
            $moneyReceived = (float)($row['MoneyReceived'] ?? 0.0);
            if ($call) {
                $moneyReceivedData[$call] = ($moneyReceivedData[$call] ?? 0.0) + $moneyReceived;
                $totalPaid += $moneyReceived;
            }
        }

        // 5) Fetch Operator Data (tbl_Operator)
        $rawOperatorData = fetchData($pdo, 'tbl_Operator', '`Call`,`FirstName`,`LastName`,`Mail-Inst`,`E-Mail`,`Address_1`,`City`,`State`,`Zip`');
        foreach ($rawOperatorData as $row) {
            $call = $row['Call'] ?? null;
            if ($call) {
                $operatorData[$call] = [
                    'firstName'     => $row['FirstName']  ?? '',
                    'lastName'      => $row['LastName']   ?? '',
                    'mailInst'      => strtolower(trim($row['Mail-Inst'] ?? '')),
                    'email'         => sanitizeEmail($row['E-Mail'] ?? ''),
                    'address'       => $row['Address_1']  ?? '',
                    'city'          => $row['City']       ?? '',
                    'state'         => $row['State']      ?? '',
                    'zip'           => $row['Zip']        ?? '',
                    'moneyReceived' => $moneyReceivedData[$call]   ?? 0,
                    'postalCost'    => $postalCostData[$call]      ?? 0,
                    'otherCost'     => $otherCostData[$call]       ?? 0
                ];
            }
        }

        // -----------------------------------------
        // Process Data to Build $redData
        // -----------------------------------------
        foreach ($operatorData as $call => $data) {
            $cardsReceived  = $cardData[$call]        ?? 0;
            $cardsMailed    = $mailedData[$call]      ?? 0;
            $cardsReturned  = $returnedData[$call]    ?? 0;
            $cardsOnHand    = $cardsReceived - $cardsMailed - $cardsReturned;
            $totalCardsOnHand += $cardsOnHand;

            $totalCost = $data['postalCost'] + $data['otherCost'];
            $total     = $data['moneyReceived'] - $totalCost;

            // For demonstration, let's show only operators who have *some* money received
            // and pass your email filters:
            if (
                $data['moneyReceived'] > 0 &&
                (
                    (!$filterEmail && !$filterNoEmail) ||
                    ($filterEmail && !empty($data['email'])) ||
                    ($filterNoEmail && empty($data['email']))
                )
            ) {
                $totalMoneyReceived += $data['moneyReceived'];
                $totalPostalCost    += $data['postalCost'];
                $totalOtherCost     += $data['otherCost'];
                $totalTotal         += $total;

                $redData[] = [
                    'Call'         => $call,
                    'FirstName'    => $data['firstName'],
                    'LastName'     => $data['lastName'],
                    'CardsOnHand'  => $cardsOnHand,
                    'MoneyReceived'=> $data['moneyReceived'],
                    'TotalCost'    => $totalCost,
                    'Total'        => $total,
                    'MailInst'     => $data['mailInst'],
                    'Email'        => $data['email'],
                    'Address'      => $data['address'],
                    'City'         => $data['city'],
                    'State'        => $data['state'],
                    'Zip'          => $data['zip']
                ];
            }
        }

        // Sort by Call
        usort($redData, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));

    } else {
        echo "Error: Invalid database configuration.";
    }
}
?>

<!-- HTML Output -->
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
        table td, table th {
            padding: 4px 8px;
            border: 1px solid #ccc;
        }
        table {
            border-collapse: collapse;
        }
    </style>

    
    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>

    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php if (isset($config['sections']) && is_array($config['sections'])): ?>
                <?php foreach ($config['sections'] as $letter => $dbInfo): ?>
                    <option value="<?= htmlspecialchars($letter) ?>"
                            <?= $selectedLetter === $letter ? 'selected' : '' ?>>
                        <?= htmlspecialchars($letter) ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">No sections available</option>
            <?php endif; ?>
        </select>

        <label for="filter_email">
            <input type="checkbox" name="filter_email" id="filter_email" <?= $filterEmail ? 'checked' : '' ?>>
            Show only users with email addresses
        </label>

        <label for="filter_no_email">
            <input type="checkbox" name="filter_no_email" id="filter_no_email" <?= $filterNoEmail ? 'checked' : '' ?>>
            Show only users without email addresses
        </label>

        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($redData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <div class="table-scroll">

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
                        <td><?= (int)$row['CardsOnHand'] ?></td>
                        <td><?= number_format($row['MoneyReceived'], 2) ?></td>
                        <td><?= number_format($row['TotalCost'], 2) ?></td>
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
                    <td><strong><?= number_format($totalMoneyReceived, 2) ?></strong></td>
                    <td><strong><?= number_format($totalPostalCost + $totalOtherCost, 2) ?></strong></td>
                    <td><strong><?= number_format($totalTotal, 2) ?></strong></td>
                    <td colspan="5"></td>
                </tr>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>

<?php
// Footer
include("$root/backend/footer.php");
?>
