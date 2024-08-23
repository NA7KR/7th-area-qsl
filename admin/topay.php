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
$title = "Users to pay Page";
$config = include($root . '/config.php');
include("$root/backend/header.php");

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$selectedLetter = null;
$filterEmail = false;
$filterNoEmail = false;
$redData = [];
$submittedData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    $filterEmail = isset($_POST['filter_email']);
    $filterNoEmail = isset($_POST['filter_no_email']);
    $printSelected = isset($_POST['print_selected']);

    if (isset($config['sections'][$selectedLetter])) {
        $dbPath = $config['sections'][$selectedLetter];
        $rawCardData = fetchData($dbPath, 'tbl_CardRec');
        if (!empty($rawCardData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawCardData)));
            $callIndex = array_search('Call', $headers);
            $cardsReceivedIndex = array_search('CardsReceived', $headers);
            foreach ($rawCardData as $row) {
                $columns = str_getcsv($row);
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
        $rawMailedData = fetchData($dbPath, 'tbl_CardM');
        if (!empty($rawMailedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawMailedData)));
            $callIndex = array_search('Call', $headers);
            $cardsMailedIndex = array_search('CardsMailed', $headers);
            $totalCostIndex = array_search('Total Cost', $headers);
            foreach ($rawMailedData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $cardsMailedIndex !== false) {
                    $call = $columns[$callIndex];
                    $cardsMailed = (int)$columns[$cardsMailedIndex];
                    $totalCost = isset($columns[$totalCostIndex]) ? (float)$columns[$totalCostIndex] : 0.0;
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
        $rawReturnedData = fetchData($dbPath, 'tbl_CardRet');
        if (!empty($rawReturnedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawReturnedData)));
            $callIndex = array_search('Call', $headers);
            $cardsReturnedIndex = array_search('CardsReturned', $headers);

            foreach ($rawReturnedData as $row) {
                $columns = str_getcsv($row);
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
        $rawMoneyReceivedData = fetchData($dbPath, 'tbl_MoneyR');
        if (!empty($rawMoneyReceivedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawMoneyReceivedData)));
            $callIndex = array_search('Call', $headers);
            $moneyReceivedIndex = array_search('MoneyReceived', $headers);
            foreach ($rawMoneyReceivedData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $moneyReceivedIndex !== false) {
                    $call = $columns[$callIndex];
                    $moneyReceived = (float)$columns[$moneyReceivedIndex];
                    if (isset($moneyReceivedData[$call])) {
                        $moneyReceivedData[$call] += $moneyReceived;
                    } else {
                        $moneyReceivedData[$call] = $moneyReceived;
                    }
                }
            }
        }
        $rawOperatorData = fetchData($dbPath, 'tbl_Operator');
        if (!empty($rawOperatorData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawOperatorData)));
            $callIndex = array_search('Call', $headers);
            $firstNameIndex = array_search('FirstName', $headers);
            $lastNameIndex = array_search('LastName', $headers);
            $mailInstIndex = array_search('Mail-Inst', $headers);
            $emailIndex = array_search('E-Mail', $headers);
            $addressIndex = array_search('Address_1', $headers);
            $cityIndex = array_search('City', $headers);
            $stateIndex = array_search('State', $headers);
            $zipIndex = array_search('Zip', $headers);
            foreach ($rawOperatorData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $mailInstIndex !== false && $emailIndex !== false) {
                    $call = $columns[$callIndex];
                    $firstName = isset($columns[$firstNameIndex]) ? $columns[$firstNameIndex] : '';
                    $lastName = isset($columns[$lastNameIndex]) ? $columns[$lastNameIndex] : '';
                    $mailInst = strtolower(trim($columns[$mailInstIndex] ?? ''));
                    $email = sanitizeEmail(trim($columns[$emailIndex] ?? ''));
                    $address = isset($columns[$addressIndex]) ? $columns[$addressIndex] : '';
                    $city = isset($columns[$cityIndex]) ? $columns[$cityIndex] : '';
                    $state = isset($columns[$stateIndex]) ? $columns[$stateIndex] : '';
                    $zip = isset($columns[$zipIndex]) ? $columns[$zipIndex] : '';
                    $operatorData[$call] = [
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'mailInst' => $mailInst,
                        'email' => $email,
                        'address' => $address,
                        'city' => $city,
                        'state' => $state,
                        'zip' => $zip
                    ];
                }
            }
        } else {
            echo "Error: tbl_Operator data is empty or could not be fetched.";
        }
        $redData = [];
        foreach ($cardData as $call => $cardsReceived) {
            $cardsMailed = $mailedData[$call] ?? 0;
            $cardsReturned = $returnedData[$call] ?? 0;
            $moneyReceived = $moneyReceivedData[$call] ?? 0;
            $totalCost = $totalCostData[$call] ?? 0;
            $cardsOnHand = $cardsReceived - $cardsMailed - $cardsReturned;
            $totalCardsOnHand += $cardsOnHand;
            $firstName = $operatorData[$call]['firstName'] ?? '';
            $lastName = $operatorData[$call]['lastName'] ?? '';
            $mailInst = $operatorData[$call]['mailInst'] ?? 'full';
            $email = $operatorData[$call]['email'] ?? '';
            $address = $operatorData[$call]['address'] ?? '';
            $city = $operatorData[$call]['city'] ?? '';
            $state = $operatorData[$call]['state'] ?? '';
            $zip = $operatorData[$call]['zip'] ?? '';
            $total = $moneyReceived - $totalCost;
            if ($cardsOnHand > 0 && $total <= 0 && 
                ((!$filterEmail && !$filterNoEmail) || 
                ($filterEmail && !empty($email)) || 
                ($filterNoEmail && empty($email)))) {
                $entry = [
                    'Call' => $call,
                    'FirstName' => $firstName,
                    'LastName' => $lastName,
                    'CardsOnHand' => $cardsOnHand,
                    'MoneyReceived' => $moneyReceived,
                    'TotalCost' => $totalCost,
                    'Total' => $total,
                    'MailInst' => $mailInst,
                    'Email' => $email,
                    'Address' => $address,
                    'City' => $city,
                    'State' => $state,
                    'Zip' => $zip
                ];
                $redData[] = $entry;
            }
        }
        usort($redData, function($a, $b) {
            return strcasecmp($a['Call'], $b['Call']);
        });

        if ($printSelected && !empty($_POST['selected_calls'])) {
            $selectedCalls = $_POST['selected_calls']; // Capture selected calls outside closure
            $submittedData = array_filter($redData, function($entry) use ($selectedCalls) {
                return in_array($entry['Call'], $selectedCalls);
            });
            $_SESSION['submittedData'] = $submittedData;
            header('Location: print_labels.php');
            exit;
        }
    } else {
        echo "Error: Invalid database configuration.";
    }
}

function fetchData($dbPath, $tableName) {
    $command = "mdb-export '$dbPath' '$tableName'";
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    if ($return_var !== 0) {
        echo "Error: Could not retrieve data from $tableName.";
        return [];
    }
    return $output;
}

function normalizeHeaders($headers) {
    return array_map('trim', $headers);
}

function sanitizeEmail($email) {
    return preg_replace('/#mailto:[^#]+#/', '', $email);
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

    <img src= "../7thArea.png" alt="7th Area" />
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
        <form method="POST">
            <input type="hidden" name="letter" value="<?= htmlspecialchars($selectedLetter) ?>">
            <input type="hidden" name="filter_email" value="<?= htmlspecialchars($filterEmail) ?>">
            <input type="hidden" name="filter_no_email" value="<?= htmlspecialchars($filterNoEmail) ?>">
            <h3>Red Table</h3>
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
                                <input type="checkbox" name="selected_calls[]" value="<?= htmlspecialchars($row['Call']) ?>">
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

<?php
include("$root/backend/footer.php");
?>
