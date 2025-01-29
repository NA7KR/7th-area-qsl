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

$title = "Users to pay Page";
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include("$root/backend/header.php");

// Check login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Include PHPMailer files
include_once("$root/backend/Exception.php");
include_once("$root/backend/PHPMailer.php");
include_once("$root/backend/SMTP.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get email config from config.php
$emailConfig = $config['email'];

// Initialize variables
$selectedLetter     = null;
$filterEmail        = false;
$filterNoEmail      = false;
$printSelected      = false; 
$cardData           = [];
$mailedData         = [];
$returnedData       = [];
$moneyReceivedData  = []; // If you eventually store money in MySQL, you'll parse it too
$totalCostData      = [];
$operatorData       = [];
$totalCardsReceived = 0;
$totalCardsMailed   = 0;
$totalCardsReturned = 0;
$totalCardsOnHand   = 0;
$submittedData      = [];

// -------------------------------------------------------------------
// MAIN EXECUTION: Handle Form Submission
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    $filterEmail    = isset($_POST['filter_email']);
    $filterNoEmail  = isset($_POST['filter_no_email']);
    $printSelected  = isset($_POST['print_selected']);

    // Get this section's MySQL credentials from config
    if (isset($config['sections'][$selectedLetter])) {
        $dbInfo = $config['sections'][$selectedLetter];

        // Build PDO
        try {
            $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['dbname']};charset=utf8";
            $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }

        // Fetch data from the 4 MySQL tables using our new function
        $rawCardData     = fetchData2($pdo, 'tbl_CardRec');
        $rawMailedData   = fetchData2($pdo, 'tbl_CardM');
        $rawReturnedData = fetchData2($pdo, 'tbl_CardRet');
        $rawOperatorData = fetchData2($pdo, 'tbl_Operator');

        // ------------------------------------------------
        // Parse tbl_CardRec -> $cardData
        // ------------------------------------------------
        if (!empty($rawCardData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawCardData), ",", "\"", "\\"));
            $callIndex         = array_search('Call',          $headers);
            $cardsReceivedIndex= array_search('CardsReceived', $headers);

            foreach ($rawCardData as $row) {
                $columns = str_getcsv($row, ",", "\"", "\\");
                if ($callIndex !== false && $cardsReceivedIndex !== false) {
                    $call = $columns[$callIndex];
                    $cardsReceived = (int)$columns[$cardsReceivedIndex];
                    if (isset($cardData[$call])) {
                        $cardData[$call] += $cardsReceived;
                    } else {
                        $cardData[$call] = $cardsReceived;
                    }
                    $totalCardsReceived += $cardsReceived;
                }
            }
        }

        // ------------------------------------------------
        // Parse tbl_CardM -> $mailedData + $totalCostData
        // ------------------------------------------------
        if (!empty($rawMailedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawMailedData), ",", "\"", "\\"));
            $callIndex         = array_search('Call',          $headers);
            $cardsMailedIndex  = array_search('CardsMailed',   $headers);
            $totalCostIndex    = array_search('Total Cost',    $headers); 
            // If your actual column name in MySQL is different, update it accordingly

            foreach ($rawMailedData as $row) {
                $columns = str_getcsv($row, ",", "\"", "\\");
                if ($callIndex !== false && $cardsMailedIndex !== false) {
                    $call        = $columns[$callIndex];
                    $cardsMailed = (int)$columns[$cardsMailedIndex];

                    $totalCost   = 0.0;
                    if ($totalCostIndex !== false && isset($columns[$totalCostIndex])) {
                        $totalCost = (float)$columns[$totalCostIndex];
                    }

                    // Accumulate
                    if (isset($mailedData[$call])) {
                        $mailedData[$call] += $cardsMailed;
                    } else {
                        $mailedData[$call] = $cardsMailed;
                    }
                    if (isset($totalCostData[$call])) {
                        $totalCostData[$call] += $totalCost;
                    } else {
                        $totalCostData[$call] = $totalCost;
                    }
                    $totalCardsMailed += $cardsMailed;
                }
            }
        }

        // ------------------------------------------------
        // Parse tbl_CardRet -> $returnedData
        // ------------------------------------------------
        if (!empty($rawReturnedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawReturnedData), ",", "\"", "\\"));
            $callIndex         = array_search('Call',          $headers);
            $cardsReturnedIndex= array_search('CardsReturned', $headers);

            foreach ($rawReturnedData as $row) {
                $columns = str_getcsv($row, ",", "\"", "\\");
                if ($callIndex !== false && $cardsReturnedIndex !== false) {
                    $call = $columns[$callIndex];
                    $cardsReturned = (int)$columns[$cardsReturnedIndex];
                    if (isset($returnedData[$call])) {
                        $returnedData[$call] += $cardsReturned;
                    } else {
                        $returnedData[$call] = $cardsReturned;
                    }
                    $totalCardsReturned += $cardsReturned;
                }
            }
        }

        // ------------------------------------------------
        // Parse tbl_Operator -> $operatorData
        // ------------------------------------------------
        if (!empty($rawOperatorData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawOperatorData), ",", "\"", "\\"));
            $callIndex      = array_search('Call',      $headers);
            $firstNameIndex = array_search('FirstName', $headers);
            $lastNameIndex  = array_search('LastName',  $headers);
            $mailInstIndex  = array_search('Mail-Inst', $headers);
            $emailIndex     = array_search('E-Mail',    $headers);
            $addressIndex   = array_search('Address_1', $headers);
            $cityIndex      = array_search('City',      $headers);
            $stateIndex     = array_search('State',     $headers);
            $zipIndex       = array_search('Zip',       $headers);

            foreach ($rawOperatorData as $row) {
                $columns = str_getcsv($row, ",", "\"", "\\");
                if ($callIndex !== false && $mailInstIndex !== false && $emailIndex !== false) {
                    $call     = $columns[$callIndex];
                    $firstName= $firstNameIndex !== false ? ($columns[$firstNameIndex] ?? '') : '';
                    $lastName = $lastNameIndex  !== false ? ($columns[$lastNameIndex]  ?? '') : '';
                    $mailInst = strtolower(trim($columns[$mailInstIndex] ?? ''));
                    $email    = sanitizeEmail(trim($columns[$emailIndex] ?? ''));
                    $address  = $addressIndex !== false ? ($columns[$addressIndex] ?? '') : '';
                    $city     = $cityIndex    !== false ? ($columns[$cityIndex]    ?? '') : '';
                    $state    = $stateIndex   !== false ? ($columns[$stateIndex]   ?? '') : '';
                    $zip      = $zipIndex     !== false ? ($columns[$zipIndex]     ?? '') : '';

                    $operatorData[$call] = [
                        'firstName' => $firstName,
                        'lastName'  => $lastName,
                        'mailInst'  => $mailInst,
                        'email'     => $email,
                        'address'   => $address,
                        'city'      => $city,
                        'state'     => $state,
                        'zip'       => $zip
                    ];
                }
            }
        } else {
            echo "Error: tbl_Operator data is empty or could not be fetched.";
        }

        // ------------------------------------------------
        // Merge data into $redData (users who owe money, etc.)
        // ------------------------------------------------
        $redData = [];
        foreach ($cardData as $call => $cardsReceived) {
            // Gather from other arrays
            $cardsMailed   = $mailedData[$call]      ?? 0;
            $cardsReturned = $returnedData[$call]    ?? 0;
            $moneyReceived = $moneyReceivedData[$call] ?? 0; // If you track it
            $totalCost     = $totalCostData[$call]   ?? 0;

            $cardsOnHand = $cardsReceived - $cardsMailed - $cardsReturned;
            $totalCardsOnHand += $cardsOnHand;

            $firstName = $operatorData[$call]['firstName'] ?? '';
            $lastName  = $operatorData[$call]['lastName']  ?? '';
            $mailInst  = $operatorData[$call]['mailInst']  ?? 'full';
            $email     = $operatorData[$call]['email']     ?? '';
            $address   = $operatorData[$call]['address']   ?? '';
            $city      = $operatorData[$call]['city']      ?? '';
            $state     = $operatorData[$call]['state']     ?? '';
            $zip       = $operatorData[$call]['zip']       ?? '';
            $total     = $moneyReceived - $totalCost; // Net balance

            // If "unpaid" (total <= 0), has cards on hand, and passes email filters
            if ($cardsOnHand > 0 && $total <= 0 &&
                ((!$filterEmail && !$filterNoEmail) ||
                 ($filterEmail && !empty($email)) ||
                 ($filterNoEmail && empty($email)))
            ) {
                $entry = [
                    'Call'         => $call,
                    'FirstName'    => $firstName,
                    'LastName'     => $lastName,
                    'CardsOnHand'  => $cardsOnHand,
                    'MoneyReceived'=> $moneyReceived,
                    'TotalCost'    => $totalCost,
                    'Total'        => $total,
                    'MailInst'     => $mailInst,
                    'Email'        => $email,
                    'Address'      => $address,
                    'City'         => $city,
                    'State'        => $state,
                    'Zip'          => $zip
                ];
                $redData[] = $entry;
            }
        }
        usort($redData, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));

        // ------------------------------------------------
        // Email Selected
        // ------------------------------------------------
        if (isset($_POST['email_selected']) && !empty($_POST['selected_calls'])) {
            foreach ($_POST['selected_calls'] as $selectedCall) {
                foreach ($redData as $data) {
                    if ($data['Call'] === $selectedCall && !empty($data['Email'])) {
                        sendEmail($data['Email'], $data['Call'], $data['CardsOnHand'], $emailConfig);
                    }
                }
            }
        }

        // ------------------------------------------------
        // Print Selected
        // ------------------------------------------------
        if ($printSelected && !empty($_POST['selected_calls'])) {
            $selectedCalls = $_POST['selected_calls'];
            $submittedData = array_filter($redData, fn($entry) => in_array($entry['Call'], $selectedCalls));
            $_SESSION['submittedData'] = $submittedData;
            header('Location: print_labels.php');
            exit;
        }
    } else {
        echo "Error: Invalid database configuration.";
    }
}
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

    
    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>

    <!-- Main Form -->
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
        <br><br>

        <label for="filter_email">
            <input type="checkbox" name="filter_email" id="filter_email" <?= $filterEmail ? 'checked' : '' ?>>
            Show only users with email addresses
        </label>
        <br><br>

        <label for="filter_no_email">
            <input type="checkbox" name="filter_no_email" id="filter_no_email" <?= $filterNoEmail ? 'checked' : '' ?>>
            Show only users without email addresses
        </label>
        <br><br>

        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($redData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Cards on Hand: <?= $totalCardsOnHand ?></p>

        <!-- Form for Email/Print actions -->
        <form method="POST">
            <input type="hidden" name="letter"        value="<?= htmlspecialchars($selectedLetter) ?>">
            <input type="hidden" name="filter_email"  value="<?= htmlspecialchars($filterEmail) ?>">
            <input type="hidden" name="filter_no_email" value="<?= htmlspecialchars($filterNoEmail) ?>">

            <h3></h3>
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" onclick="toggleCheckbox(this)"> Select All</th>
                        <th>Call</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Cards On Hand</th>
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
                            <td>
                                <input type="checkbox" 
                                       name="selected_calls[]" 
                                       value="<?= htmlspecialchars($row['Call']) ?>">
                            </td>
                            <td><?= htmlspecialchars($row['Call']) ?></td>
                            <td><?= htmlspecialchars($row['FirstName']) ?></td>
                            <td><?= htmlspecialchars($row['LastName']) ?></td>
                            <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                            <td><?= htmlspecialchars($row['Email']) ?></td>
                            <td><?= htmlspecialchars($row['Address']) ?></td>
                            <td><?= htmlspecialchars($row['City']) ?></td>
                            <td><?= htmlspecialchars($row['State']) ?></td>
                            <td><?= htmlspecialchars($row['Zip']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit" name="email_selected">Email Selected</button>
            <button type="submit" name="print_selected">Print Selected</button>
        </form>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>

    <?php if (!empty($submittedData)): ?>
        <h2>Selected Data for Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>Cards On Hand</th>
                    <th>Money Received</th>
                    <th>Total Cost</th>
                    <th>Total</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Zip</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submittedData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Call']) ?></td>
                        <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                        <td><?= htmlspecialchars($row['MoneyReceived']) ?></td>
                        <td><?= htmlspecialchars($row['TotalCost']) ?></td>
                        <td><?= htmlspecialchars($row['Total']) ?></td>
                        <td><?= htmlspecialchars($row['Email']) ?></td>
                        <td><?= htmlspecialchars($row['Address']) ?></td>
                        <td><?= htmlspecialchars($row['City']) ?></td>
                        <td><?= htmlspecialchars($row['State']) ?></td>
                        <td><?= htmlspecialchars($row['Zip']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
/**
 * Toggles all checkboxes in the table.
 */
function toggleCheckbox(source) {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name="selected_calls[]"]');
    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

<?php
include("$root/backend/footer.php");
?>
