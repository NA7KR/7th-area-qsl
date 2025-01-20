<?php
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Cards Mailed Out";
$debug = false;

include("$root/backend/header.php");
$config = include($root . '/config.php');

/**
 * Fetches data from MySQL using PDO, returning CSV-like lines so parseMailedData() stays unchanged.
 *
 * @param PDO    $pdo               The PDO connection to the MySQL database.
 * @param string $tableName         Table from which to retrieve data.
 * @param string $startDate         Optional start date (YYYY-MM-DD) for filtering.
 * @param string $endDate           Optional end date (YYYY-MM-DD) for filtering.
 * @param bool   $enableDateFilter  Whether or not to apply date range filtering.
 * @param bool   $debug             If true, echoes the SQL query for debugging.
 *
 * @return string[] An array of CSV lines (header + data).
 */
function fetchData(PDO $pdo, $tableName, $startDate = null, $endDate = null, $enableDateFilter = false, $debug = false)
{
    // Assuming these columns exist in your MySQL table:
    //   Call (VARCHAR), CardsMailed (INT), DateMailed (DATE or DATETIME)
    $query = "SELECT `Call`, `CardsMailed`, `DateMailed` FROM `$tableName`";
    $conditions = [];

    if ($enableDateFilter && $startDate && $endDate) {
        // Compare date range in MySQL
        // Make sure the table column is actually typed as DATE/DATETIME (or at least a parseable format).
        $conditions[] = "`DateMailed` BETWEEN :startDate AND :endDate";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    if ($debug) {
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

    // Convert the result set into CSV-like lines for parseMailedData().
    // The first line is the CSV header that parseMailedData() expects.
    $output = [];
    $output[] = "Call,CardsMailed,DateMailed"; // match the columns we selected

    foreach ($rows as $row) {
        // Safely handle null fields
        $call        = '"' . ($row['Call']        ?? '') . '"';
        $cardsMailed = '"' . ($row['CardsMailed'] ?? '') . '"';
        $dateMailed  = '"' . ($row['DateMailed']  ?? '') . '"';

        // Create a CSV line
        $output[] = implode(',', [$call, $cardsMailed, $dateMailed]);
    }

    return $output;
}

/**
 * Parses CSV-like data for "Call" and "CardsMailed" columns, returning an array plus total cards mailed.
 *
 * @param string[] $rawData CSV lines (first line = header, subsequent lines = data).
 *
 * @return array [ (array $mailedData), (int $totalCardsMailed) ]
 */
function parseMailedData($rawData)
{
    if (empty($rawData)) {
        return [[], 0];
    }

    // First line is the CSV header
    $headers = str_getcsv(array_shift($rawData));
    $callIndex         = array_search('Call',         $headers);
    $cardsMailedIndex  = array_search('CardsMailed',  $headers);

    if ($callIndex === false || $cardsMailedIndex === false) {
        echo "Error: Could not find required columns in the data.";
        return [[], 0];
    }

    $mailedData = [];
    $totalCardsMailed = 0;

    foreach ($rawData as $row) {
        $columns = str_getcsv($row);
        if (isset($columns[$callIndex]) && isset($columns[$cardsMailedIndex])) {
            $call         = $columns[$callIndex];
            $cardsMailed  = (int)$columns[$cardsMailedIndex];
            $mailedData[] = [
                'Call'        => $call,
                'CardsMailed' => $cardsMailed
            ];
            $totalCardsMailed += $cardsMailed;
        }
    }

    // Sort by Call
    usort($mailedData, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));

    return [$mailedData, $totalCardsMailed];
}

/**
 * Handles form submission, opens a PDO connection, fetches & parses data, and returns array with results.
 */
function handleFormSubmission($config, $debug)
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
    $rawMailedData = fetchData($pdo, 'tbl_CardM', $startDate, $endDate, $enableDateFilter, $debug);

    // Parse into array & total
    return [$selectedLetter, parseMailedData($rawMailedData)];
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
    var checkbox = document.getElementById('dateFilterCheckbox');
    var dateFilters = document.getElementById('dateFilters');
    dateFilters.style.display = checkbox.checked ? 'block' : 'none';
}
</script>

<?php include("$root/backend/footer.php"); ?>
