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
$title = "Total Cards Received";
include("$root/backend/header.php");
$config = include($root . '/config.php');
$debug = false;

function fetchData($dbPath, $tableName, $startDate = null, $endDate = null, $debug = false) {
    // Only format the dates if they are provided
    $startDate = $startDate ? date("m/d/y", strtotime($startDate)) : null;
    $endDate = $endDate ? date("m/d/y", strtotime($endDate)) : null;

    $escapedDbPath = escapeshellarg($dbPath);
    $escapedTableName = escapeshellarg($tableName);

    // Construct the awk command with date filtering and include the header row explicitly
    $awkCommand = "| awk -F',' 'NR==1{print \$0; next} {split(\$4, date_time, \" \"); date = date_time[1]; gsub(/\"/, \"\", date); if (date >= \"$startDate\" && date <= \"$endDate\") print \$0}'";
    
    if ($startDate && $endDate) {
        $command = "mdb-export $escapedDbPath $escapedTableName $awkCommand";
    } elseif ($startDate) {
        $command = "mdb-export $escapedDbPath $escapedTableName | awk -F',' 'NR==1{print \$0; next} {split(\$4, date_time, \" \"); date = date_time[1]; gsub(/\"/, \"\", date); if (date >= \"$startDate\") print \$0}'";
    } elseif ($endDate) {
        $command = "mdb-export $escapedDbPath $escapedTableName | awk -F',' 'NR==1{print \$0; next} {split(\$4, date_time, \" \"); date = date_time[1]; gsub(/\"/, \"\", date); if (date <= \"$endDate\") print \$0}'";
    } else {
        $command = "mdb-export $escapedDbPath $escapedTableName";
    }

    if ($debug) {
        echo "Debug: Start Date = $startDate<br>";
        echo "Debug: End Date = $endDate<br>";
        echo "Debug: Command: " . htmlspecialchars($command) . "<br>";
    }

    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);

    if ($debug) {
        echo "Debug: Command Return Value: " . htmlspecialchars($return_var) . "<br>";
        echo "Debug: Raw Output: <pre>" . htmlspecialchars(implode("\n", $output)) . "</pre><br>";
    }

    if ($return_var !== 0 || empty($output)) {
        echo "Error: Could not retrieve data.";
        return [];
    }

    // Parse the CSV data
    $headers = str_getcsv(array_shift($output));
    $callIndex = array_search('Call', $headers);
    $cardsReceivedIndex = array_search('CardsReceived', $headers);

    if ($callIndex === false || $cardsReceivedIndex === false) {
        echo "Error: Could not find required columns in the data.";
        return [];
    }

    $aggregatedData = [];
    $totalCardsOnHand = 0;

    foreach ($output as $row) {
        $columns = str_getcsv($row);
        $call = $columns[$callIndex];
        $cardsReceived = (int) $columns[$cardsReceivedIndex];
        
        if (isset($aggregatedData[$call])) {
            $aggregatedData[$call] += $cardsReceived;
        } else {
            $aggregatedData[$call] = $cardsReceived;
        }
        $totalCardsOnHand += $cardsReceived;
    }

    return [
        'data' => $aggregatedData,
        'total' => $totalCardsOnHand
    ];
}

$selectedLetter = null;
$data = [];
$totalCardsOnHand = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    $startDate = null;
    $endDate = null;

    // Only set startDate and endDate if the date filter checkbox is checked
    if (isset($_POST['dateFilterCheckbox'])) {
        $startDate = $_POST['startDate'] ?? null;
        $endDate = $_POST['endDate'] ?? null;
    }

    if (isset($config['sections'][$selectedLetter])) {
        $dbPath = $config['sections'][$selectedLetter];
        $result = fetchData($dbPath, 'tbl_CardRec', $startDate, $endDate, $debug);
        if (!empty($result)) {
            $data = $result['data'];
            $totalCardsOnHand = $result['total'];
            arsort($data); // Sort by CardsReceived in descending order
        }
    } else {
        echo "Error: Invalid database configuration.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Cards Received</title>
    <link rel="stylesheet" href="/styles.css"> <!-- Update the path accordingly -->
</head>
<body>
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
            
            <label for="dateFilterCheckbox">Enable Date Filter:</label>
            <input type="checkbox" id="dateFilterCheckbox" name="dateFilterCheckbox" onclick="toggleDateFilters()" <?= isset($_POST['dateFilterCheckbox']) ? 'checked' : '' ?>>

            <div id="dateFilters" style="display: <?= isset($_POST['dateFilterCheckbox']) ? 'block' : 'none' ?>;">
                <label for="startDate">Start Date:</label>
                <input type="date" id="startDate" name="startDate" value="<?= htmlspecialchars($_POST['startDate'] ?? '') ?>">
                
                <label for="endDate">End Date:</label>
                <input type="date" id="endDate" name="endDate" value="<?= htmlspecialchars($_POST['endDate'] ?? '') ?>">
            </div>
            
            <button type="submit">Submit</button>
        </form>

        <?php if (!empty($data)): ?>
            <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
            <p>Total Cards Handled: <?= htmlspecialchars($totalCardsOnHand) ?></p>
            <table>
                <thead>
                    <tr>
                        <th>Call</th>
                        <th>Cards Received</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $call => $cardsReceived): ?>
                        <tr>
                            <td><?= htmlspecialchars($call) ?></td>
                            <td><?= htmlspecialchars($cardsReceived) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($selectedLetter !== null): ?>
            <p>No data found or there was an error retrieving the data.</p>
        <?php endif; ?>
    </div>

    <script>
        function toggleDateFilters() {
            var checkbox = document.getElementById('dateFilterCheckbox');
            var dateFilters = document.getElementById('dateFilters');
            dateFilters.style.display = checkbox.checked ? 'block' : 'none';
        }
    </script>
</body>
</html>

<?php
include("$root/backend/footer.php");
?>
