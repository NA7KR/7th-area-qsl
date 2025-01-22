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

namespace Disposedr;

use PDO;
use PDOException;

session_start();
$root = realpath($_SERVER["DOCUMENT_ROOT"]);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Cards Disposed";
$selectedLetter   = null;
$dataRows         = [];
$queryInfo        = [];
$totalCardsReturned = 0;

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
function getPDOConnection(array $dbInfo): PDO
{
    try {
        $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['dbname']};charset=utf8";
        $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new \RuntimeException("Database connection failed.");
    }
}

/**
 * Fetch all rows grouped by Call and add CardsReturned to display total.
 */
function fetchGroupedData(PDO $pdo, string $tableName): array
{
    $query = "SELECT `Call`, COUNT(*) AS `CardsReturned` FROM `$tableName` GROUP BY `Call` ORDER BY `Call` ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error retrieving data: " . $e->getMessage());
        throw new \RuntimeException("Error retrieving data.");
    }
}

function fetchAllExpenses(PDO $pdo, string $tableName, ?string $startDate = null, ?string $endDate = null, ?string $dateColumn = null): array
{
    $query = "SELECT * FROM `$tableName` ";
    $params = [];

    if ($dateColumn && $startDate && $endDate) {
        $query .= " WHERE `$dateColumn` BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate']   = $endDate;
    }

    $query .= " ORDER BY `ID` ASC";
//echo $query;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'query' => $query,
            'params' => $params
        ];
    } catch (PDOException $e) {
        error_log("Error retrieving expense data: " . $e->getMessage());
        throw new \RuntimeException("Error retrieving expense data.");
    }
}

// ----------------- MAIN -----------------
$dataRows = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedLetter   = $_POST['letter'] ?? null;
    $enableDateFilter = isset($_POST['dateFilterCheckbox']);
    $startDate        = $_POST['startDate'] ?? null;
    $endDate          = $_POST['endDate'] ?? null;

    if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
        $dbInfo = $config['sections'][$selectedLetter];
        $pdo    = getPDOConnection($dbInfo);

        // Fetch data with or without date filtering
        if ($enableDateFilter && $startDate && $endDate) {
            $result = fetchAllExpenses($pdo, 'tbl_CardRet', $startDate, $endDate, 'DateReturned');
        } else {
            $result = fetchAllExpenses($pdo, 'tbl_CardRet');
        }

        $dataRows = $result['data'];
        $queryInfo = [
            'query' => $result['query'],
            'params' => $result['params']
        ];
    } else {
        echo "Error: Invalid or missing section configuration.";
    }
}


?>

<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau - Expenses Tracker</h1>

    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php
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
               onclick="toggleDateFilters()"
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
    
    
    <?php if (!empty($dataRows)): ?>
        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>Cards Returned</th>
                    
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dataRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Call'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['CardsReturned'] ?? 'N/A') ?></td>
                        <?php
                        $totalCardsReturned += $row['CardsReturned'];
                        ?>

                        <td><?= htmlspecialchars($row['reason'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3>Total Cards Returned: <?= htmlspecialchars($totalCardsReturned) ?></h3>
    <?php else: ?>
        <p>No disposed cards found.</p>
    <?php endif; ?>
        
    <!-- Assuming the rest of your body content is here -->
</body>
</html>
<script>
function toggleDateFilters() {
    var checkbox = document.getElementById('dateFilterCheckbox');
    var dateFilters = document.getElementById('dateFilters');
    dateFilters.style.display = checkbox.checked ? 'block' : 'none';
}
</script>

<?php
include("$root/backend/footer.php");
?>
