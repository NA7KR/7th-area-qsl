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

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Stamp Tracker";

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

include("$root/backend/header.php");
$config = include($root . '/config.php');

/**
 * Create a PDO connection using config array: ['host','dbname','username','password'].
 */
function getPDOConnection(array $dbInfo)
{
    try {
        $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['dbname']};charset=utf8";
        $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Fetch all rows from tbl_Stamps, optionally with date filtering on $dateColumn.
 * We do NOT do grouping in SQL here, so we can apply config-based values in PHP.
 */
function fetchAllStamps(PDO $pdo, $tableName, $startDate = null, $endDate = null, $dateColumn = null)
{
    $query = "SELECT * FROM `$tableName`";
    $params = [];

    if ($dateColumn && $startDate && $endDate) {
        $query .= " WHERE `$dateColumn` BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate']   = $endDate;
    }

    // Maybe sort by date or ID, up to you
    $query .= " ORDER BY `ID` ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error retrieving stamp data: " . $e->getMessage());
    }
}

/**
 * Group rows by `Value` in PHP, summing QTY Purchased/Used, 
 * then calculating "Stamps On Hand", "Total Purchased", "Cost of Postage"
 * using config-based stamp values (if any).
 */
function parseAndAggregate(array $rows, array $config): array
{
    // We'll accumulate into $aggregated[$valueName].
    $aggregated = [];

    foreach ($rows as $r) {
        // The columns have spaces, so we must reference them in $r exactly:
        $valueRaw       = $r['Value']            ?? ''; // e.g. "Forever", "Additional Ounce", "0.01"
        $qtyPurchased   = $r['QTY Purchased']    ?? 0;  // int
        $qtyUsed        = $r['QTY Used']         ?? 0;  // int

        // Convert them to int
        $qtyPurchased   = (int)$qtyPurchased;
        $qtyUsed        = (int)$qtyUsed;

        // If config has a custom numeric for this value
        // e.g. $config['stamps']['Forever'] = 0.63
        if (isset($config['stamps'][$valueRaw])) {
            $stampNumericValue = (float)$config['stamps'][$valueRaw];
        } else {
            // Attempt to parse the string as a float (like "0.01")
            // If not numeric, it becomes 0
            $stampNumericValue = (float)$valueRaw;
        }

        // Initialize aggregator if not set
        if (!isset($aggregated[$valueRaw])) {
            $aggregated[$valueRaw] = [
                'Value'          => $valueRaw, // to display
                'QTY Purchased'  => 0,
                'QTY Used'       => 0,
                'Stamps On Hand' => 0,
                'Total Purchased'=> 0.0,
                'Cost of Postage'=> 0.0,
                'NumericValue'   => $stampNumericValue // store for calculations
            ];
        }

        // Accumulate
        $aggregated[$valueRaw]['QTY Purchased'] += $qtyPurchased;
        $aggregated[$valueRaw]['QTY Used']      += $qtyUsed;
    }

    // After summing QTYs, compute 'Stamps On Hand', 'Total Purchased', 'Cost of Postage'
    foreach ($aggregated as $valueKey => &$stamp) {
        $purchased = $stamp['QTY Purchased'];
        $used      = $stamp['QTY Used'];
        $numeric   = $stamp['NumericValue']; // e.g. 0.63 for Forever

        $stamp['Stamps On Hand'] = $purchased - $used;
        // total purchased = purchased * numeric
        $stamp['Total Purchased'] = round($purchased * $numeric, 2);
        // cost of postage = used * numeric
        $stamp['Cost of Postage'] = round($used * $numeric, 2);
    }
    unset($stamp);

    // Convert from assoc to indexed array
    return array_values($aggregated);
}

// ----------------- MAIN -----------------
$selectedLetter   = null;
$dataRows         = [];  // raw MySQL rows
$aggregatedStamps = [];  // after aggregator

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedLetter   = $_POST['letter']            ?? null;
    $enableDateFilter = isset($_POST['dateFilterCheckbox']);
    $startDate        = $_POST['startDate']         ?? null;
    $endDate          = $_POST['endDate']           ?? null;

    if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
        $dbInfo = $config['sections'][$selectedLetter];
        $pdo    = getPDOConnection($dbInfo);

        // fetch all rows from tbl_Stamps
        if ($enableDateFilter && $startDate && $endDate) {
            $dataRows = fetchAllStamps($pdo, 'tbl_Stamps', $startDate, $endDate, 'DateAdded');
        } else {
            $dataRows = fetchAllStamps($pdo, 'tbl_Stamps');
        }

        // now parse & aggregate them
        $aggregatedStamps = parseAndAggregate($dataRows, $config);
        // sort them by Value (alphabetically, or numeric if all are numeric)
        usort($aggregatedStamps, function($a, $b) {
            // If you want numeric sort, do: 
            // return ($a['NumericValue'] <=> $b['NumericValue']);
            return strcasecmp($a['Value'], $b['Value']);
        });
    } else {
        echo "Error: Invalid or missing section configuration.";
    }
}
?>

<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau - Stamp Tracker</h1>

    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php
            // If config[sections] is set, show each letter
            if (isset($config['sections']) && is_array($config['sections'])):
                foreach ($config['sections'] as $letter => $dbCreds):
            ?>
                    <option value="<?= htmlspecialchars($letter ?? '') ?>" 
                            <?= ($selectedLetter === $letter) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($letter ?? '') ?>
                    </option>
            <?php
                endforeach;
            else:
            ?>
                <option value="">No sections available</option>
            <?php endif; ?>
        </select>

        <label for="dateFilterCheckbox">Enable Date Filter:</label>
        <input type="checkbox" id="dateFilterCheckbox" name="dateFilterCheckbox"
               onclick="toggleDateFilters(); toggleColumns();"
               <?= isset($_POST['dateFilterCheckbox']) ? 'checked' : '' ?>>

        <div id="dateFilters" style="display: <?= isset($_POST['dateFilterCheckbox']) ? 'block' : 'none' ?>;">
            <label for="startDate">Start Date:</label>
            <input type="date" id="startDate" name="startDate"
                   value="<?= htmlspecialchars($_POST['startDate'] ?? '') ?>">

            <label for="endDate">End Date:</label>
            <input type="date" id="endDate" name="endDate"
                   value="<?= htmlspecialchars($_POST['endDate'] ?? '') ?>">
        </div>

        <button type="submit">Submit</button>
    </form>

    <h2>Stamp Summary</h2>

    <?php if (!empty($aggregatedStamps)): ?>
        <table>
            <thead>
                <tr>
                    <th>Value Of Stamps</th>
                    <th class="qty-purchased">QTY Purchased</th>
                    <th>QTY Used</th>
                    <th class="stamps-on-hand">Stamps On Hand</th>
                    <th class="total-purchased">Total Purchased</th>
                    <th>Cost of Postage</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalPurchased = 0;
                $totalUsed = 0;
                $totalOnHand = 0;
                $totalPurchasedValue = 0.0;
                $totalCostOfPostage = 0.0;

                foreach ($aggregatedStamps as $row): 
                    $totalPurchased += $row['QTY Purchased'];
                    $totalUsed += $row['QTY Used'];
                    $totalOnHand += $row['Stamps On Hand'];
                    $totalPurchasedValue += $row['Total Purchased'];
                    $totalCostOfPostage += $row['Cost of Postage'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Value'] ?? '') ?></td>
                        <td class="qty-purchased"><?= (int)$row['QTY Purchased'] ?></td>
                        <td><?= (int)$row['QTY Used'] ?></td>
                        <td class="stamps-on-hand"><?= (int)$row['Stamps On Hand'] ?></td>
                        <td class="total-purchased"><?= number_format($row['Total Purchased'], 2) ?></td>
                        <td><?= number_format($row['Cost of Postage'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th class="qty-purchased"><?= (int)$totalPurchased ?></th>
                    <th><?= (int)$totalUsed ?></th>
                    <th class="stamps-on-hand"><?= (int)$totalOnHand ?></th>
                    <th class="total-purchased"><?= number_format($totalPurchasedValue, 2) ?></th>
                    <th><?= number_format($totalCostOfPostage, 2) ?></th>
                </tr>
            </tfoot>
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

function toggleColumns() {
    var checkbox = document.getElementById('dateFilterCheckbox');
    var columns = document.querySelectorAll('.qty-purchased, .stamps-on-hand, .total-purchased');
    columns.forEach(function(column) {
        column.style.display = checkbox.checked ? 'none' : 'table-cell';
    });
}

// Initial call to set the correct column visibility on page load
toggleColumns();
</script>

<?php
include("$root/backend/footer.php");
?>
