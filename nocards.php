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
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
session_start();
$title = "Filtered Calls";
include("$root/backend/header.php");
$config = include($root . '/config.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
// Enable debugging
$debug = false;

function fetchData($dbPath, $tableName) {
    global $debug;
    $command = "mdb-export '$dbPath' '$tableName'";
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    if ($return_var !== 0) {
        echo "Error: Could not retrieve data.";
        if ($debug) {
            error_log("Debug: Failed to execute command: $command");
            echo "<p>Debug: Failed to execute command: $command</p>";
        }
        return [];
    }
    if ($debug) {
        error_log("Debug: Successfully executed command: $command");
        error_log("Debug: Output: " . print_r($output, true));
        echo "<p>Debug: Successfully executed command: $command</p>";
        echo "<pre>Debug: Output: " . print_r($output, true) . "</pre>";
    }
    return $output;
}

$selectedLetter = null;
$data = [];
$totalCardsOnHand = 0;
$totalMoneyReceived = 0;
$totalPaid = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    if ($debug) {
        error_log("Debug: Selected letter: $selectedLetter");
        echo "<p>Debug: Selected letter: $selectedLetter</p>";
    }
    if (isset($config['sections'][$selectedLetter])) {
        $dbPath = $config['sections'][$selectedLetter];

        // Fetch data from tbl_CardRec
        $rawCardRecData = fetchData($dbPath, 'tbl_CardRec');
        $cardRecData = [];
        if (!empty($rawCardRecData)) {
            $headers = str_getcsv(array_shift($rawCardRecData));
            $callIndex = array_search('Call', $headers);
            $cardsReceivedIndex = array_search('CardsReceived', $headers);

            if ($callIndex === false || $cardsReceivedIndex === false) {
                echo "<p>Debug: Missing required columns in tbl_CardRec.</p>";
                error_log("Debug: Missing required columns in tbl_CardRec.");
            } else {
                foreach ($rawCardRecData as $row) {
                    $columns = str_getcsv($row);
                    $call = $columns[$callIndex];
                    $cardsReceived = (int)$columns[$cardsReceivedIndex];
                    if (!isset($cardRecData[$call])) {
                        $cardRecData[$call] = 0;
                    }
                    $cardRecData[$call] += $cardsReceived;
                }
            }
        }

        // Fetch data from tbl_CardRet
        $rawCardRetData = fetchData($dbPath, 'tbl_CardRet');
        $cardRetData = [];
        if (!empty($rawCardRetData)) {
            $headers = str_getcsv(array_shift($rawCardRetData));
            $callIndex = array_search('Call', $headers);
            $cardsReturnedIndex = array_search('CardsReturned', $headers);

            if ($callIndex === false || $cardsReturnedIndex === false) {
                echo "<p>Debug: Missing required columns in tbl_CardRet.</p>";
                error_log("Debug: Missing required columns in tbl_CardRet.");
            } else {
                foreach ($rawCardRetData as $row) {
                    $columns = str_getcsv($row);
                    $call = $columns[$callIndex];
                    $cardsReturned = (int)$columns[$cardsReturnedIndex];
                    if (!isset($cardRetData[$call])) {
                        $cardRetData[$call] = 0;
                    }
                    $cardRetData[$call] += $cardsReturned;
                }
            }
        }

        // Fetch data from tbl_MoneyR
        $rawMoneyRData = fetchData($dbPath, 'tbl_MoneyR');
        $moneyRData = [];
        if (!empty($rawMoneyRData)) {
            $headers = str_getcsv(array_shift($rawMoneyRData));
            $callIndex = array_search('Call', $headers);
            $moneyReceivedIndex = array_search('MoneyReceived', $headers);

            if ($callIndex === false || $moneyReceivedIndex === false) {
                echo "<p>Debug: Missing required columns in tbl_MoneyR.</p>";
                error_log("Debug: Missing required columns in tbl_MoneyR.");
            } else {
                foreach ($rawMoneyRData as $row) {
                    $columns = str_getcsv($row);
                    $call = $columns[$callIndex];
                    $moneyReceived = (float)$columns[$moneyReceivedIndex];
                    if (!isset($moneyRData[$call])) {
                        $moneyRData[$call] = 0;
                    }
                    $moneyRData[$call] += $moneyReceived;
                }
            }
        }

        // Fetch data from tbl_CardM
        $rawCardMData = fetchData($dbPath, 'tbl_CardM');
        $cardMData = [];
        if (!empty($rawCardMData)) {
            $headers = str_getcsv(array_shift($rawCardMData));
            $callIndex = array_search('Call', $headers);
            $postalCostIndex = array_search('PostalCost', $headers);
            $otherCostIndex = array_search('OtherCost', $headers);

            if ($callIndex === false || $postalCostIndex === false || $otherCostIndex === false) {
                echo "<p>Debug: Missing required columns in tbl_CardM.</p>";
                error_log("Debug: Missing required columns in tbl_CardM.");
            } else {
                foreach ($rawCardMData as $row) {
                    $columns = str_getcsv($row);
                    $call = $columns[$callIndex];
                    $postalCost = (float)$columns[$postalCostIndex];
                    $otherCost = (float)$columns[$otherCostIndex];
                    if (!isset($cardMData[$call])) {
                        $cardMData[$call] = 0;
                    }
                    $cardMData[$call] += $postalCost + $otherCost;
                }
            }
        }

        // Fetch data from tbl_Operator
        $rawOperatorData = fetchData($dbPath, 'tbl_Operator');
        $ignoreStatuses = $config['ignore_statuses'];
        if (!empty($rawOperatorData)) {
            $headers = str_getcsv(array_shift($rawOperatorData));
            $callIndex = array_search('Call', $headers);
            $statusIndex = array_search('Status', $headers);
            $remarksIndex = array_search('Remarks', $headers);
            $licExpIndex = array_search('Lic-exp', $headers);

            if ($callIndex === false || $statusIndex === false || $remarksIndex === false || $licExpIndex === false) {
                echo "<p>Debug: Missing required columns in tbl_Operator.</p>";
                error_log("Debug: Missing required columns in tbl_Operator.");
            } else {
                foreach ($rawOperatorData as $row) {
                    $columns = str_getcsv($row);
                    $call = $columns[$callIndex];
                    $status = $columns[$statusIndex];
                    $remarks = $columns[$remarksIndex];
                    $licExp = $columns[$licExpIndex];
                    if (in_array($status, $ignoreStatuses)) {
                        $cardsReceived = isset($cardRecData[$call]) ? $cardRecData[$call] : 0;
                        $cardsReturned = isset($cardRetData[$call]) ? $cardRetData[$call] : 0;
                        $moneyReceived = isset($moneyRData[$call]) ? $moneyRData[$call] : 0;
                        $paid = isset($cardMData[$call]) ? $cardMData[$call] : 0;
                        $data[] = [
                            'Call' => $call,
                            'CardsReceived' => $cardsReceived,
                            'CardsReturned' => $cardsReturned,
                            'MoneyReceived' => $moneyReceived,
                            'Paid' => $paid,
                            'Status' => $status,
                            'Remarks' => $remarks,
                            'Lic-exp' => $licExp
                        ];
                        $totalCardsOnHand += $cardsReceived;
                        $totalMoneyReceived += $moneyReceived;
                        $totalPaid += $paid;
                    }
                }
            }
        }

        usort($data, function($a, $b) {
            return $b['CardsReceived'] <=> $a['CardsReceived'];
        });

        if ($debug) {
            error_log("Debug: Data sorted: " . print_r($data, true));
            error_log("Debug: Total Cards On Hand: $totalCardsOnHand");
            error_log("Debug: Total Money Received: $totalMoneyReceived");
            error_log("Debug: Total Paid: $totalPaid");
            echo "<pre>Debug: Data sorted: " . print_r($data, true) . "</pre>";
            echo "<p>Debug: Total Cards On Hand: $totalCardsOnHand</p>";
            echo "<p>Debug: Total Money Received: $totalMoneyReceived</p>";
            echo "<p>Debug: Total Paid: $totalPaid</p>";
        }
    } else {
        echo "Error: Invalid database configuration.";
        if ($debug) {
            error_log("Debug: Invalid section letter: $selectedLetter");
            echo "<p>Debug: Invalid section letter: $selectedLetter</p>";
        }
    }
}
?>
    <div class="center-content">
        <img src="7thArea.png" alt="7th Area" />
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

    <?php if (!empty($data)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Total Cards Handled: <?= htmlspecialchars($totalCardsOnHand) ?></p>
        <p>Total Money Received: <?= htmlspecialchars($totalMoneyReceived) ?></p>
        <p>Total Paid: <?= htmlspecialchars($totalPaid) ?></p>
        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>Cards Received</th>
                    <th>Cards Returned</th>
                    <th>Money Received</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Lic-exp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Call']) ?></td>
                        <td><?= htmlspecialchars($row['CardsReceived']) ?></td>
                        <td><?= htmlspecialchars($row['CardsReturned']) ?></td>
                        <td><?= htmlspecialchars($row['MoneyReceived']) ?></td>
                        <td><?= htmlspecialchars($row['Paid']) ?></td>
                        <td><?= htmlspecialchars($row['Status']) ?></td>
                        <td><?= htmlspecialchars($row['Remarks']) ?></td>
                        <td><?= htmlspecialchars($row['Lic-exp']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
<?php
include("$root/backend/footer.php");
?>
