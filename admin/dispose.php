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
$title = "Cards Disposed";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("$root/backend/header.php");
$config = include($root . '/config.php');

$debug = false;

// Function to fetch data from the specified table using mdbtools
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

// Function to parse CSV data into an associative array
function parseCsvData($rawData) {
    $parsedData = [];
    $totalCardsReturned = 0;

    if (!empty($rawData)) {
        $headers = str_getcsv(array_shift($rawData), ",", "\"", "\\"); // Fixed
        $callIndex = array_search('Call', $headers);
        $cardsReturnedIndex = array_search('CardsReturned', $headers);

        foreach ($rawData as $row) {
            $columns = str_getcsv($row, ",", "\"", "\\"); // Fixed
            if ($callIndex !== false && $cardsReturnedIndex !== false) {
                $call = $columns[$callIndex];
                $cardsReturned = (int)$columns[$cardsReturnedIndex];

                if (isset($parsedData[$call])) {
                    $parsedData[$call]['CardsReturned'] += $cardsReturned;
                } else {
                    $parsedData[$call] = [
                        'Call' => $call,
                        'CardsReturned' => $cardsReturned,
                    ];
                }
                $totalCardsReturned += $cardsReturned;
            }
        }

        // Sort parsed data by Call column
        usort($parsedData, function ($a, $b) {
            return strcasecmp($a['Call'], $b['Call']);
        });
    }

    return [$parsedData, $totalCardsReturned];
}

// Function to handle form submission
function handleFormSubmission($config) {
    $selectedLetter = null;
    $mailedData = [];
    $totalCardsReturned = 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
        $selectedLetter = $_POST['letter'];

        if (isset($config['sections'][$selectedLetter])) {
            $dbPath = $config['sections'][$selectedLetter];

            // Fetch data from tbl_CardRet
            $rawMailedData = fetchData($dbPath, 'tbl_CardRet');
            list($mailedData, $totalCardsReturned) = parseCsvData($rawMailedData);
        } else {
            echo "Error: Invalid database configuration.";
        }
    }

    return [$selectedLetter, $mailedData, $totalCardsReturned];
}

list($selectedLetter, $mailedData, $totalCardsReturned) = handleFormSubmission($config);
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

    <?php if (!empty($mailedData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Cards Disposed: <?= $totalCardsReturned ?></p>

        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>Cards Disposed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mailedData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Call']) ?></td>
                        <td><?= htmlspecialchars($row['CardsReturned']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>

    <?php include("$root/backend/footer.php"); ?>
</div>
