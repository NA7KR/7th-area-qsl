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

$title = "Stamp Tracker";
include("$root/backend/header.php");
$config = include($root . '/config.php');

$debug = false;

function fetchData($dbPath, $tableName, $debug = false) {
    $command = "mdb-export \"$dbPath\" \"$tableName\"";
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

function parseData($rawData, $config, $debug = false) {
    $data = [];
    $totals = [
        'stampsOnHand' => 0,
        'stampsUsed' => 0,
        'costOfPostage' => 0,
        'purchaseCost' => 0,
    ];

    $headers = str_getcsv(array_shift($rawData));
    $idIndex = array_search('ID', $headers);
    $valueIndex = array_search('Value', $headers);
    $qtyPurchasedIndex = array_search('QTY Purchased', $headers);
    $qtyUsedIndex = array_search('QTY Used', $headers);

    if ($idIndex === false || $valueIndex === false || $qtyPurchasedIndex === false || $qtyUsedIndex === false) {
        echo "<p>Debug: Missing required columns in tbl_Stamps.</p>";
        error_log("Debug: Missing required columns in tbl_Stamps.");
        return [$data, $totals];
    }

    foreach ($rawData as $row) {
        $columns = str_getcsv($row);
        $value = $columns[$valueIndex];
        
        $value2 = $columns[$valueIndex];
        
        $qtyPurchased = (int)$columns[$qtyPurchasedIndex];
        $qtyUsed = (int)$columns[$qtyUsedIndex];

        if (isset($config['stamps'][$value])) {
            $value = $config['stamps'][$value];
        }

        $value = (float) str_replace(',', '.', $value);

        $stampsOnHand = $qtyPurchased - $qtyUsed;
        $costOfPostage = $qtyUsed * $value;
        $total = $qtyPurchased * $value;

        $data[] = [
            'Value' => $value,
            'Value2' => $value2,
            'QTY Purchased' => $qtyPurchased,
            'QTY Used' => $qtyUsed,
            'Stamps On Hand' => $stampsOnHand,
            'Cost of Postage' => $costOfPostage,
            'Total' => $total,
        ];

        $totals['stampsOnHand'] += $stampsOnHand;
        $totals['stampsUsed'] += $qtyUsed;
        $totals['costOfPostage'] += $costOfPostage;
        $totals['purchaseCost'] += $total;

        if ($debug) {
            echo "<p>Debug: Value: $value, Value2: $value2, QTY Purchased: $qtyPurchased, QTY Used: $qtyUsed, Stamps On Hand: $stampsOnHand, Cost of Postage: $costOfPostage, Total: $total</p>";
            error_log("Debug: Value: $value, Value2: $value2, QTY Purchased: $qtyPurchased, QTY Used: $qtyUsed, Stamps On Hand: $stampsOnHand, Cost of Postage: $costOfPostage, Total: $total");
        }
    }

    return [$data, $totals];
}

$dbPath = $config['sections']['F'];
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

?>
<div class="center-content">
    <img src="7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area Stamp Tracker</h1>

    <h2>Stamp Summary</h2>
    <p>Total Stamps On Hand: <?= htmlspecialchars($totals['stampsOnHand']) ?></p>
    <p>Total Stamps Used: <?= htmlspecialchars($totals['stampsUsed']) ?></p>
    <p>Total Cost of Postage Used: $<?= htmlspecialchars($totals['costOfPostage']) ?></p>
    <p>Total Purchase Cost All: $<?= htmlspecialchars($totals['purchaseCost']) ?></p> 

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
