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

$root   = realpath($_SERVER["DOCUMENT_ROOT"]);
$title  = "Cards Mailed Out";
$debug  = false;

include("$root/backend/header.php");
$config = include($root . '/config.php');

/**
 * Fetches data from MySQL using PDO, returning CSV-like lines so parseMailedData() stays unchanged.
 *
 * @param PDO    $pdo               PDO connection to the MySQL database
 * @param string $tableName         The table from which to retrieve data (e.g., 'tbl_CardM')
 * @param string $startDate         Optional start date (YYYY-MM-DD) for filtering
 * @param string $endDate           Optional end date (YYYY-MM-DD) for filtering
 * @param bool   $enableDateFilter  Whether to apply the date range filtering
 * @param bool   $debugMode         If true, echoes the SQL query for debugging
 *
 * @return string[] Array of CSV lines (the first line is a header, subsequent lines are data)
 */
function fetchData(
    PDO $pdo,
    string $tableName,
    ?string $startDate = null,
    ?string $endDate = null,
    bool $enableDateFilter = false,
    bool $debugMode = false
): array {
    // Assuming the table has columns: Call (VARCHAR), CardsMailed (INT), DateMailed (DATE/DATETIME)
    $query      = "SELECT `Call`, `CardsMailed`, `DateMailed` FROM `$tableName`";
    $conditions = [];

    if ($enableDateFilter && $startDate && $endDate) {
        // Ensure your actual table column is typed as DATE/DATETIME
        $conditions[] = "`DateMailed` BETWEEN :startDate AND :endDate";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    if ($debugMode) {
        echo "Debug: SQL Query: " . htmlspecialchars($query) . "<br>";
    }

    try {
        $stmt = $pdo->prepare($query);

        if ($enableDateFilter && $startDate && $endDate) {
            // Bind :startDate and :endDate as strings in YYYY-MM-DD format
            $stmt->bindValue(':startDate', $startDate);
            $stmt->bindValue(':endDate',   $endDate);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }

    // Convert the result set into CSV lines for parseMailedData().
    // The first line is the CSV header that parseMailedData() expects.
    $csvLines   = [];
    $csvLines[] = "Call,CardsMailed,DateMailed"; // matches the columns we selected

    foreach ($rows as $resultRow) {
        // Safely handle potential null fields
        $call        = '"' . ($resultRow['Call']        ?? '') . '"';
        $cardsMailed = '"' . ($resultRow['CardsMailed'] ?? '') . '"';
        $dateMailed  = '"' . ($resultRow['DateMailed']  ?? '') . '"';

        // Create a CSV line
        $csvLines[] = implode(',', [$call, $cardsMailed, $dateMailed]);
    }

    return $csvLines;
}

/**
 * Parses CSV-like data for "Call" and "CardsMailed" columns, returning an array plus total cards mailed.
 * 
 * @param string[] $rawData CSV lines (the first line is the header, subsequent lines are data)
 *
 * @return array [ (array $mailedData), (int $totalCardsMailed) ]
 */
function parseMailedData(array $rawData): array
{
    if (empty($rawData)) {
        return [[], 0];
    }

    // First line is the CSV header
    $headers           = str_getcsv(array_shift($rawData), ",", "\"", "\\");
    $callIndex         = array_search('Call',        $headers);
    $cardsMailedIndex  = array_search('CardsMailed', $headers);

    if ($callIndex === false || $cardsMailedIndex === false) {
        echo "Error: Could not find the required columns in the data.";
        return [[], 0];
    }

    $mailedData       = [];
    $totalCardsMailed = 0;

    foreach ($rawData as $line) {
        $columns = str_getcsv($line, ",", "\"", "\\");
        if (isset($columns[$callIndex]) && isset($columns[$cardsMailedIndex])) {
            $call              = $columns[$callIndex];
            $cardsMailed       = (int) $columns[$cardsMailedIndex];
            $mailedData[]      = [
                'Call'        => $call,
                'CardsMailed' => $cardsMailed,
            ];
            $totalCardsMailed += $cardsMailed;
        }
    }

    // Sort by Call sign
    usort($mailedData, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));

    return [$mailedData, $totalCardsMailed];
}

/**
 * Handles form submission, opens a PDO connection, fetches & parses data, and returns an array with results.
 *
 * @param array $config    Configuration array including 'sections' with DB credentials
 * @param bool  $debugMode Whether to echo the SQL query for debugging
 *
 * @return array [ (string|null $selectedLetter), (array $parsedData) ]
 */
function handleFormSubmission(array $config, bool $debugMode): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return [null, [[], 0]];
    }

    $selectedLetter   = $_POST['letter']            ?? null;
    $enableDateFilter = isset($_POST['dateFilterCheckbox']);
    $startDate        = $_POST['startDate']         ?? null; // e.g., "2025-01-01"
    $endDate          = $_POST['endDate']           ?? null; // e.g., "2025-01-30"

    if (!$selectedLetter || !isset($config['sections'][$selectedLetter])) {
        echo "Error: Invalid database configuration.";
        return [null, [[], 0]];
    }

    // Build PDO connection from config
    $dbInfo = $config['sections'][$selectedLetter];

    try {
        $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['dbname']};charset=utf8";
        $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Fetch from tbl_CardM
    $csvMailedData = fetchData($pdo, 'tbl_CardM', $startDate, $endDate, $enableDateFilter, $debugMode);

    // Parse into array & total
    return [$selectedLetter, parseMailedData($csvMailedData)];
}

// ------------------------------------------------------------------
// MAIN CODE
// ------------------------------------------------------------------
list($selectedLetter, $parsedData) = handleFormSubmission($config, $debug);
$mailedData       = $parsedData[0];
$totalCardsMailed = $parsedData[1];
?>

<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>

    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php foreach ($config['sections'] as $letter => $dbInfo): ?>
                <option value="<?= htmlspecialchars($letter) ?>"
                        <?= $selectedLetter === $letter ? 'selected' : '' ?>>
                    <?= htmlspecialchars($letter) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="dateFilterCheckbox">Enable Date Filter:</label>
        <input type="checkbox"
               id="dateFilterCheckbox"
               name="dateFilterCheckbox"
               onclick="toggleDateFilters()"
               <?= isset($_POST['dateFilterCheckbox']) ? 'checked' : '' ?>>

        <div id="dateFilters" style="display: <?= isset($_POST['dateFilterCheckbox']) ? 'block' : 'none' ?>;">
            <label for="startDate">Start Date:</label>
            <input type="date"
                   id="startDate"
                   name="startDate"
                   value="<?= htmlspecialchars($_POST['startDate'] ?? '') ?>">

            <label for="endDate">End Date:</label>
            <input type="date"
                   id="endDate"
                   name="endDate"
                   value="<?= htmlspecialchars($_POST['endDate'] ?? '') ?>">
        </div>

        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($mailedData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Cards Mailed: <?= $totalCardsMailed ?></p>

        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>Cards Mailed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mailedData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Call']) ?></td>
                        <td><?= htmlspecialchars($row['CardsMailed']) ?></td>
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
    var checkbox    = document.getElementById('dateFilterCheckbox');
    var dateFilters = document.getElementById('dateFilters');
    dateFilters.style.display = checkbox.checked ? 'block' : 'none';
}
</script>

<?php include("$root/backend/footer.php"); ?>
