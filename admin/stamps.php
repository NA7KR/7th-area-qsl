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
error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Stamp Tracker";
include("$root/backend/header.php");
$config = include($root . '/config.php');

$debug = false;

// Function to fetch data from the specified table using mdbtools
function fetchData($dbPath, $tableName, $startDate = null, $endDate = null, $dateColumn = null) {
    // Verify that the database path exists
    if (!file_exists($dbPath)) {
        echo "Error: Database file not found at path: $dbPath";
        return [];
    }
    $startDate = $startDate ? date("m/d/y", strtotime($startDate)) : null;
    $endDate = $endDate ? date("m/d/y", strtotime($endDate)) : null;
    $dateFieldNumber = 6; // Adjust if necessary

    // Construct the basic command for MDBTools
    $command = "mdb-export '$dbPath' '$tableName'";

    // Apply date filtering using the specified date column, only if both dates are provided
    if (!empty($startDate) && !empty($endDate) && !empty($dateColumn)) {
        $command .= " | awk -F, 'BEGIN {OFS=\",\"} { dateField=substr(\$$dateFieldNumber, 2, 8); if (dateField >= \"$startDate\" && dateField <= \"$endDate\") print }'";
    }

    // Debug: Print the exact command being executed
    if ($GLOBALS['debug']) {
        echo "<p>Debug: Executing command: $command</p>";
        echo "<p>Debug: Date Column: $dateColumn</p>";
    }

    // Execute the command and capture the output
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);

    // Debug: Print the output of the command
    if ($GLOBALS['debug']) {
        echo "<p>Debug: Command output:</p>";
        echo "<pre>" . implode("\n", $output) . "</pre>";
    }

    // Check if the command failed
    if ($return_var !== 0) {
        echo "Error: Could not retrieve data from $tableName. Command executed: $command";
        return [];
    }

    // Check if the output is empty
    if (empty($output)) {
        echo "Error: No data retrieved from $tableName. It might be an empty table or an issue with the query.";
        return [];
    }

    return $output;
}

$selectedLetter = null;
$mailedData = [];
$totalCardsMailed = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    $startDate = $_POST['startDate'] ?? null;
    $endDate = $_POST['endDate'] ?? null;
    if (isset($config['sections'][$selectedLetter])) {
        $dbPath = $config['sections'][$selectedLetter];

        // Fetch data from tbl_CardM with DateMailed filtering
        $rawMailedData = fetchData($dbPath, 'tbl_CardM', $startDate, $endDate, 'DateMailed');
        if (!empty($rawMailedData)) {
            $headers = str_getcsv(array_shift($rawMailedData));
            $callIndex = array_search('Call', $headers);
            $cardsMailedIndex = array_search('CardsMailed', $headers);

            foreach ($rawMailedData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $cardsMailedIndex !== false) {
                    $cardsMailed = (int)$columns[$cardsMailedIndex];
                    $mailedData[] = [
                        'Call' => $columns[$callIndex],
                        'CardsMailed' => $cardsMailed
                    ];
                    $totalCardsMailed += $cardsMailed;
                }
            }

            usort($mailedData, function($a, $b) {
                return strcasecmp($a['Call'], $b['Call']);
            });
        }
    } else {
        echo "Error: Invalid database configuration.";
    }
}

function parseData($rawData, $config, $debug = false) {
    $data = [];
    $aggregatedData = [];
    $totals = [
        'stampsOnHand' => 0,
        'stampsUsed' => 0,
        'costOfPostage' => '0',
        'purchaseCost' => '0',
    ];

    if (empty($rawData) || !is_array($rawData)) {
        echo "<p>Debug: No data to process in tbl_Stamps.</p>";
        error_log("Debug: No data to process in tbl_Stamps.");
        return [$data, $totals];
    }

    $headers = str_getcsv(array_shift($rawData));
    if ($headers === false) {
        echo "<p>Debug: Failed to parse headers in tbl_Stamps.</p>";
        error_log("Debug: Failed to parse headers in tbl_Stamps.");
        return [$data, $totals];
    }

    // Adjust these variable names if the column names in your database are different
    $valueIndex = 1; // Column where stamp value is stored
    $qtyPurchasedIndex = 2; // Column for quantity purchased
    $qtyUsedIndex = 3; // Column for quantity used

    if ($valueIndex === false || $qtyPurchasedIndex === false || $qtyUsedIndex === false) {
        echo "<p>Debug: Missing required columns in tbl_Stamps.</p>";
        error_log("Debug: Missing required columns in tbl_Stamps.");
        return [$data, $totals];
    }

    foreach ($rawData as $row) {
        $columns = str_getcsv($row);
        if ($columns === false) {
            echo "<p>Debug: Failed to parse a row in tbl_Stamps.</p>";
            error_log("Debug: Failed to parse a row in tbl_Stamps.");
            continue;
        }

        $value = trim((string) $columns[$valueIndex]);
        $qtyPurchased = (int) $columns[$qtyPurchasedIndex];
        $qtyUsed = (int) $columns[$qtyUsedIndex];

        $calculationValue = isset($config['stamps'][$value]) ? $config['stamps'][$value] : $value;

        if (!isset($aggregatedData[$value])) {
            $aggregatedData[$value] = [
                'Value' => $value,
                'Value2' => $value,
                'QTY Purchased' => 0,
                'QTY Used' => 0,
                'Stamps On Hand' => 0,
                'Cost of Postage' => '0',
                'Total' => '0',
            ];
        }

        $aggregatedData[$value]['QTY Purchased'] += $qtyPurchased;
        $aggregatedData[$value]['QTY Used'] += $qtyUsed;
        $aggregatedData[$value]['Stamps On Hand'] = $aggregatedData[$value]['QTY Purchased'] - $aggregatedData[$value]['QTY Used'];

        $costOfPostage = bcmul((string)$qtyUsed, is_numeric($calculationValue) ? $calculationValue : '0', 2);
        $aggregatedData[$value]['Cost of Postage'] = bcadd($aggregatedData[$value]['Cost of Postage'], $costOfPostage, 2);

        $total = bcmul((string)$qtyPurchased, is_numeric($calculationValue) ? $calculationValue : '0', 2);
        $aggregatedData[$value]['Total'] = bcadd($aggregatedData[$value]['Total'], $total, 2);

        $totals['stampsOnHand'] += $aggregatedData[$value]['Stamps On Hand'];
        $totals['stampsUsed'] += $qtyUsed;
        $totals['costOfPostage'] = bcadd($totals['costOfPostage'], $costOfPostage, 2);
        $totals['purchaseCost'] = bcadd($totals['purchaseCost'], $total, 2);
    }

    $data = array_values($aggregatedData);

    return [$data, $totals];
}

if ($selectedLetter !== null) {
    $dbPath = $config['sections'][$selectedLetter];
    // Fetch data from tbl_Stamps with DateAdded filtering
    $rawStampData = fetchData($dbPath, 'tbl_Stamps', $_POST['startDate'], $_POST['endDate'], 'DateAdded');
    list($data, $totals) = parseData($rawStampData, $config, $debug);

    usort($data, function($a, $b) {
        return $a['Value'] <=> $b['Value'];
    });

    if ($debug) {
        echo "<p>Debug: Total Stamps On Hand: {$totals['stampsOnHand']}</p>";
        echo "<p>Debug: Total Stamps Used: {$totals['stampsUsed']}</p>";
        echo "<p>Debug: Total Cost of Postage: {$totals['costOfPostage']}</p>";
        echo "<p>Debug: Total Purchase Cost: {$totals['purchaseCost']}</p>";
        error_log("Debug: Total Stamps On Hand: {$totals['stampsOnHand']}");
        error_log("Debug: Total Stamps Used: {$totals['stampsUsed']}");
        error_log("Debug: Total Cost of Postage: {$totals['costOfPostage']}");
        error_log("Debug: Total Purchase Cost: {$totals['purchaseCost']}");
    }
}
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

    <h2>Stamp Summary</h2>
    <?php if (!isset($_POST['dateFilterCheckbox'])): ?>
        <!--- <p>Total Stamps On Hand: <?= htmlspecialchars($totals['stampsOnHand'] ?? 0) ?></p> -->
        <p>Total Purchase Cost All: $<?= htmlspecialchars($totals['purchaseCost'] ?? 0) ?></p> 
    <?php endif; ?>
    <!--- <p>Total Stamps Used: <?= htmlspecialchars($totals['stampsUsed'] ?? 0) ?></p> -->
    <p>Total Cost of Postage Used: $<?= htmlspecialchars($totals['costOfPostage'] ?? 0) ?></p>

    <?php if (!empty($data)): ?>
        <table>
            <thead>
                <tr>
                    <th>Value Of Stamps</th>
                    <?php if (!isset($_POST['dateFilterCheckbox'])): ?>
                        <th>QTY Purchased</th>
                    <?php endif; ?>
                    <th>QTY Used</th>
                    <?php if (!isset($_POST['dateFilterCheckbox'])): ?>
                        <th>Stamps On Hand</th>
                        <th>Total Purchased</th>
                    <?php endif; ?>
                    <th>Cost of Postage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Value2']) ?></td>
                        <?php if (!isset($_POST['dateFilterCheckbox'])): ?>
                            <td><?= htmlspecialchars($row['QTY Purchased']) ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($row['QTY Used']) ?></td>
                        <?php if (!isset($_POST['dateFilterCheckbox'])): ?>
                            <td><?= htmlspecialchars($row['Stamps On Hand']) ?></td>
                            <td><?= htmlspecialchars($row['Total']) ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($row['Cost of Postage']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>

<script>
function toggleDateFilters() {
    var dateFilterCheckbox = document.getElementById('dateFilterCheckbox');
    var dateFilters = document.getElementById('dateFilters');
    dateFilters.style.display = dateFilterCheckbox.checked ? 'block' : 'none';
}
</script>

<?php
include("$root/backend/footer.php");
?>
