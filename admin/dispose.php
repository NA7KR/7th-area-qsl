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

namespace Disposer;

use PDO;
use PDOException;

session_start();
$root = realpath($_SERVER["DOCUMENT_ROOT"]);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Cards Disposed";
$selectedSection = null;
$dataRows = [];
$queryInfo = [];
$totalCardsReturnedSum = 0;

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
 * Fetch all rows from tbl_CardRet joined with tbl_Operator to get Status.
 */
function fetchJoinedData(PDO $pdo, string $tableName1, string $tableName2, ?string $startDate = null, ?string $endDate = null): array
{
    $query = "SELECT tbl_CardRet.Call, SUM(tbl_CardRet.CardsReturned) AS TotalCardsReturned, MAX(tbl_Operator.`Status`) AS Status
              FROM `$tableName1`
              INNER JOIN `$tableName2` ON tbl_CardRet.Call = tbl_Operator.Call
              WHERE tbl_CardRet.Call IS NOT NULL AND tbl_Operator.Call IS NOT NULL";

    $params = [];

    if ($startDate && $endDate) {
        $query .= " AND tbl_CardRet.DateReturned BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate'] = $endDate;
    }

    $query .= " GROUP BY tbl_CardRet.Call ORDER BY tbl_CardRet.Call ASC";

    //echo $query; // Debugging: Output the constructed query

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'query' => $query,
            'params' => $params
        ];
    } catch (PDOException $e) {
        error_log("Error retrieving data: " . $e->getMessage());
        throw new \RuntimeException("Error retrieving data.");
    }
}

// ----------------- MAIN -----------------
$dataRows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSection = $_POST['section'] ?? null;
    $enableDateFilter = isset($_POST['dateFilterCheckbox']);
    $startDate = $_POST['startDate'] ?? null;
    $endDate = $_POST['endDate'] ?? null;

    if ($selectedSection && isset($config['sections'][$selectedSection])) {
        $dbInfo = $config['sections'][$selectedSection];
        $pdo = getPDOConnection($dbInfo);

        // Fetch data with or without date filtering
        $result = fetchJoinedData($pdo, 'tbl_CardRet', 'tbl_Operator', $enableDateFilter ? $startDate : null, $enableDateFilter ? $endDate : null);

        $dataRows = $result['data'] ?? [];
        $queryInfo = [
            'query' => $result['query'] ?? '',
            'params' => $result['params'] ?? []
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
        <label for="section">Select a Section:</label>
        <select name="section" id="section">
            <?php
            if (isset($config['sections']) && is_array($config['sections'])):
                foreach ($config['sections'] as $section => $dbCreds):
            ?>
                    <option value="<?= htmlspecialchars($section ?? '') ?>" 
                            <?= ($selectedSection === $section) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($section ?? '') ?>
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
                    <th>Total Cards Returned</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dataRows as $row): ?>
                    <?php 
                    $call = $row['Call'] ?? 'N/A';
                    $totalCardsReturned = $row['TotalCardsReturned'] ?? 'N/A';
                    $status = $row['Status'] ?? 'N/A';
                    if ($call !== 'N/A' && $totalCardsReturned !== 'N/A'): ?>
                        <tr>
                            <td><?= htmlspecialchars($call) ?></td>
                            <td><?= htmlspecialchars($totalCardsReturned) ?></td>
                            <?php $totalCardsReturnedSum += $totalCardsReturned; ?>
                            <td><?= htmlspecialchars($status) ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3>Total Cards Returned: <?= htmlspecialchars((string)$totalCardsReturnedSum) ?></h3>
    <?php else: ?>
        <p>No disposed cards found.</p>
    <?php endif; ?>
</div>

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
