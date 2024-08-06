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

// Enable debugging
$debug = false;

function fetchData($dbPath, $tableName) {
    global $debug;
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

$data = [];
$totalStampsOnHand = 0;
$totalStampsUsed = 0;
$totalCostOfPostage = 0;
$totalPurchaseCost = 0;

$dbPath = $config['sections']['F'];
$rawStampData = fetchData($dbPath, 'tbl_Stamps');

if (!empty($rawStampData)) {
    $headers = str_getcsv(array_shift($rawStampData));
    $idIndex = array_search('ID', $headers);
    $valueIndex = array_search('Value', $headers);
    $qtyPurchasedIndex = array_search('QTY Purchased', $headers);
    $qtyUsedIndex = array_search('QTY Used', $headers);

    if ($idIndex === false || $valueIndex === false || $qtyPurchasedIndex === false || $qtyUsedIndex === false) {
        echo "<p>Debug: Missing required columns in tbl_Stamps.</p>";
        error_log("Debug: Missing required columns in tbl_Stamps.");
    } else {
        foreach ($rawStampData as $row) {
            $columns = str_getcsv($row);
            $value = $columns[$valueIndex];
            $qtyPurchased = (int)$columns[$qtyPurchasedIndex];
            $qtyUsed = (int)$columns[$qtyUsedIndex];
            
            // Replace value with corresponding postage cost if it matches
            if (isset($config['stamps'][$value])) {
                $value = $config['stamps'][$value];
            }
            
            // Convert value to float for calculations
            $value = (float) str_replace(',', '.', $value);
            
            $stampsOnHand = $qtyPurchased - $qtyUsed;
            $costOfPostage = $qtyUsed * $value;
            $total = $qtyPurchased * $value;

            $data[] = [
                'Value' => $value,
                'QTY Purchased' => $qtyPurchased,
                'QTY Used' => $qtyUsed,
                'Stamps On Hand' => $stampsOnHand,
                'Cost of Postage' => $costOfPostage,
                'Total' => $total
            ];

            $totalStampsOnHand += $stampsOnHand;
            $totalStampsUsed += $qtyUsed;
            $totalCostOfPostage += $costOfPostage;
            $totalPurchaseCost += $total;

            if ($debug) {
                echo "<p>Debug: Value: $value, QTY Purchased: $qtyPurchased, QTY Used: $qtyUsed, Stamps On Hand: $stampsOnHand, Cost of Postage: $costOfPostage, Total: $total</p>";
                error_log("Debug: Value: $value, QTY Purchased: $qtyPurchased, QTY Used: $qtyUsed, Stamps On Hand: $stampsOnHand, Cost of Postage: $costOfPostage, Total: $total");
            }
        }
    }
}

// Sort the data by 'Value' in ascending order
usort($data, function($a, $b) {
    return $a['Value'] <=> $b['Value'];
});

if ($debug) {
    echo "<p>Debug: Total Stamps On Hand: $totalStampsOnHand</p>";
    echo "<p>Debug: Total Stamps Used: $totalStampsUsed</p>";
    echo "<p>Debug: Total Cost of Postage: $totalCostOfPostage</p>";
    echo "<p>Debug: Total Purchase Cost: $totalPurchaseCost</p>";
    error_log("Debug: Total Stamps On Hand: $totalStampsOnHand");
    error_log("Debug: Total Stamps Used: $totalStampsUsed");
    error_log("Debug: Total Cost of Postage: $totalCostOfPostage");
    error_log("Debug: Total Purchase Cost: $totalPurchaseCost");
}

?>
<div class="center-content">
    <img src="7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area Stamp Tracker</h1>

    <h2>Stamp Summary</h2>
    <p>Total Stamps On Hand: <?= htmlspecialchars($totalStampsOnHand) ?></p>
    <p>Total Stamps Used: <?= htmlspecialchars($totalStampsUsed) ?></p>
    <p>Total Cost of Postage: $<?= htmlspecialchars($totalCostOfPostage ) ?></p>
    <p>Total Purchase Cost: $<?= htmlspecialchars($totalPurchaseCost) ?></p> 

    <?php if (!empty($data)): ?>
        <table>
            <thead>
                <tr>
                    <th>Value Of Stamps</th>
                    <th>QTY Purchased</th>
                    <th>QTY Used</th>
                    <th>Stamps On Hand</th>
                    <th>Cost of Postage</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Value']) ?></td>
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
