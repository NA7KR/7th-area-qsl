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
//print_r($_POST);
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
 */
function fetchAllStamps(PDO $pdo, $tableName)
{
    $query = "SELECT * FROM `$tableName`";
    $params = [];
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
$selectedLetter = null;
$call = null;
$CardsToMail = null;
$weight = null;
$PostageCost = null;
$OtherCost = null;
$TotalCost = null;
$ID = null;
$dataRows = [];
$aggregatedStamps = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedLetter = $_POST['letter'] ?? null;
    $call = $_POST['Call'] ?? null;
    $CardsToMail = $_POST['CardsToMail'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $PostageCost = $_POST['PostageCost'] ?? null;
    $OtherCost = $_POST['OtherCost'] ?? null;
    $TotalCost = $_POST['TotalCost'] ?? null;
    $ID = $_POST['ID'] ?? null;
    if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
        $dbInfo = $config['sections'][$selectedLetter];
        $pdo = getPDOConnection($dbInfo);

        $dataRows = fetchAllStamps($pdo, 'tbl_Stamps');

        $aggregatedStamps = parseAndAggregate($dataRows, $config);
        usort($aggregatedStamps, function ($a, $b) {
            return strcasecmp($a['Value'], $b['Value']);
        });
    } else {
        echo "Error: Invalid or missing section configuration.";
    }
}
?>

<div class="center-content">
    <h1 class="my-4 text-center">7th Area QSL Bureau - Stamp Tracker</h1>

    <form method="POST">
        <button type="submit">Submit</button>
    </form>

    <h2>Stamp Summary</h2>
    <?php echo "Call: $call"; ?><br>
    <?php echo "CardsToMail: $CardsToMail"; ?><br>
    <?php echo "weight: $weight"; ?><br>
    <?php echo "PostageCost: $PostageCost"; ?><br>
    <?php echo "OtherCost: OtherCost"; ?><br>
    <?php echo "TotalCost: $TotalCost"; ?><br>
    <?php echo "ID: $ID"; ?><br>
    <?php if (!empty($aggregatedStamps)): ?>
        <table>
            <thead>
                <tr>
                    <th>Value Of Stamps</th>
                    <th class="stamps-on-hand">Stamps On Hand</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalOnHand = 0;

                foreach ($aggregatedStamps as $row): 
                    $totalOnHand += $row['Stamps On Hand'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Value'] ?? '') ?></td>
                        <td class="stamps-on-hand"><?= (int)$row['Stamps On Hand'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>
