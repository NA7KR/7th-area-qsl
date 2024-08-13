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

$debug = true;

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

// Handle form submission
$selectedLetter = null;
$mailedData = [];
$totalCardsMailed = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    if (isset($config['sections'][$selectedLetter])) {
        $dbPath = $config['sections'][$selectedLetter];

        // Fetch data from tbl_CardM
        $rawMailedData = fetchData($dbPath, 'tbl_CardM');
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

            // Sort mailed data by Call column
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

    $headers = str_getcsv(array_shift($rawData));
    $valueIndex = array_search('Value', $headers);
    $qtyPurchasedIndex = array_search('QTY Purchased', $headers);
    $qtyUsedIndex = array_search('QTY Used', $headers);

    if ($valueIndex === false || $qtyPurchasedIndex === false || $qtyUsedIndex === false) {
        echo "<p>Debug: Missing required columns in tbl_Stamps.</p>";
        error_log("Debug: Missing required columns in tbl_Stamps.");
        return [$data, $totals];
    }

    foreach ($rawData as $row) {
        $columns = str_getcsv($row);
        $value = trim((string) $columns[$valueIndex]);  // Treat value as a string
        $qtyPurchased = (int) $columns[$qtyPurchasedIndex];
        $qtyUsed = (int) $columns[$qtyUsedIndex];

        // If the value exists in the config, use the config value for calculations
        $calculationValue = isset($config['stamps'][$value]) ? $config['stamps'][$value] : $value;

        if (!isset($aggregatedData[$value])) {
            $aggregatedData[$value] = [
                'Value' => $value,
                'Value2' => $value, // Ensure Value2 is always set
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

        // Calculate Cost of Postage and Total safely using the config value
        $costOfPostage = bcmul((string)$qtyUsed, is_numeric($calculationValue) ? $calculationValue : '0', 2);
        $aggregatedData[$value]['Cost of Postage'] = bcadd($aggregatedData[$value]['Cost of Postage'], $costOfPostage, 2);

        $total = bcmul((string)$qtyPurchased, is_numeric($calculationValue) ? $calculationValue : '0', 2);
        $aggregatedData[$value]['Total'] = bcadd($aggregatedData[$value]['Total'], $total, 2);

        // Update totals
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
    $rawStampData = fetchData($dbPath, 'tbl_Stamps', $debug);
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
        <button type="submit">Submit</button>
    </form>

    <h2>Stamp Summary</h2>
    <p>Total Stamps On Hand: <?= htmlspecialchars($totals['stampsOnHand'] ?? 0) ?></p>
    <p>Total Stamps Used: <?= htmlspecialchars($totals['stampsUsed'] ?? 0) ?></p>
    <p>Total Cost of Postage Used: $<?= htmlspecialchars($totals['costOfPostage'] ?? 0) ?></p>
    <p>Total Purchase Cost All: $<?= htmlspecialchars($totals['purchaseCost'] ?? 0) ?></p> 

    <?php if (!empty($data)): ?>
        <table>
            <thead>
                <tr>
                    <th>Value Of Stamps</th>
                    <th>QTY Purchased</th>
                    <th>QTY Used</th>
                    <th>Stamps On Hand</th>
                    <th>Cost of Postage</th>
                    <th>Total Purchased</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Value2']) ?></td>
                        <td><?= htmlspecialchars($row['QTY Purchased']) ?></td>
                        <td><?= htmlspecialchars($row['QTY Used']) ?></td>
                        <td><?= htmlspecialchars($row['Stamps On Hand']) ?></td>
                        <td><?= htmlspecialchars($row['Cost of Postage']) ?></td>
                        <td><?= htmlspecialchars($row['Total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>
<?php
include("$root/backend/footer.php");
?>
