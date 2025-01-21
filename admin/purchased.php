<?php
/*
 * Example page that reads rows from tbl_Purchased, optionally filters by Date,
 * and aggregates results so each unique Item is listed once, summing Qty, counting
 * how many times Refunded=1, etc.
 */

session_start();
$root = realpath($_SERVER["DOCUMENT_ROOT"]);

// Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Purchase Reader Page";

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

include("$root/backend/header.php");
$config = include($root . '/config.php');

/**
 * Get a PDO connection from config credentials: sections[$letter] => [host, dbname, username, password].
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
 * Fetch all rows from tbl_Purchased, optionally date-filtered by `Date` column.
 */
function fetchPurchasedRows(PDO $pdo, $startDate = null, $endDate = null)
{
    $query = "SELECT * FROM `tbl_Purchased`";
    $params = [];

    if ($startDate && $endDate) {
        $query .= " WHERE `Date` BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate']   = $endDate;
    }

    // Possibly sort by `ID` or `Date`
    $query .= " ORDER BY `ID` ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching purchased data: " . $e->getMessage());
    }
}

/**
 * Aggregate rows by `Item`. 
 * For each unique Item:
 *   - Sum total Qty
 *   - Count how many rows had Refunded=1
 *   - Also track the distinct "Bureau", "Own", etc. if you want them
 */
function aggregateByItem(array $rows): array
{
    $aggregated = [];

    foreach ($rows as $r) {
        // Access columns:
        //  'Item', 'Qty', 'Date', 'Own', 'Refunded', 'Bureau', 'Field1'
        $item     = $r['Item']     ?? '';
        $qty      = (int)($r['Qty'] ?? 0);
        $refunded = (int)($r['Refunded'] ?? 0);

        // Initialize aggregator if not done
        if (!isset($aggregated[$item])) {
            $aggregated[$item] = [
                'Item'          => $item,
                'TotalQty'      => 0,    // sum of Qty
                'RefundCount'   => 0,    // how many times refunded=1
                'AnyBureau'     => '',   // might store one sample or something
                'AnyOwner'      => '',   // etc.
            ];
        }

        // Accumulate
        $aggregated[$item]['TotalQty']    += $qty;
        if ($refunded === 1) {
            $aggregated[$item]['RefundCount'] += 1;
        }

        // If you want to track "Own" or "Bureau", decide how to store them
        // e.g. store the first non-empty or a list of unique values
        // For now, let's just store the last one we see
        $aggregated[$item]['AnyBureau'] = $r['Bureau'] ?? '';
        $aggregated[$item]['AnyOwner']  = $r['Own']    ?? '';
    }

    // Convert from assoc to a simple array
    return array_values($aggregated);
}

// --------------------------------------
// MAIN Execution Flow
// --------------------------------------
$selectedLetter  = $_POST['letter']   ?? null;
$startDate       = $_POST['startDate'] ?? null; // 'YYYY-MM-DD'
$endDate         = $_POST['endDate']   ?? null; 
$enableDateFilter= isset($_POST['dateFilterCheckbox']);

// final aggregated data
$aggregatedData  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
        // 1) Connect
        $pdo = getPDOConnection($config['sections'][$selectedLetter]);

        // 2) Fetch
        if ($enableDateFilter && $startDate && $endDate) {
            $rows = fetchPurchasedRows($pdo, $startDate, $endDate);
        } else {
            $rows = fetchPurchasedRows($pdo);
        }

        // 3) Aggregate
        $aggregatedData = aggregateByItem($rows);

        // 4) Sort by item name (alphabetical)
        usort($aggregatedData, function($a, $b) {
            return strcasecmp($a['Item'], $b['Item']);
        });
    } else {
        echo "Error: Invalid or missing section configuration.";
    }
}
?>

<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">Purchases Overview</h1>

    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php if (isset($config['sections']) && is_array($config['sections'])): ?>
                <?php foreach ($config['sections'] as $letter => $dbInfo): ?>
                    <option value="<?= htmlspecialchars($letter ?? '') ?>"
                            <?= ($selectedLetter === $letter) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($letter ?? '') ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
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

    <?php if (!empty($aggregatedData)): ?>
        <h2>Aggregated by Item</h2>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Total Qty</th>
                    <th>Refund Count</th>
                    <th>Any Bureau</th>
                    <th>Any Owner</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aggregatedData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Item']         ?? '') ?></td>
                        <td><?= (int)$row['TotalQty'] ?></td>
                        <td><?= (int)$row['RefundCount'] ?></td>
                        <td><?= htmlspecialchars($row['AnyBureau']    ?? '') ?></td>
                        <td><?= htmlspecialchars($row['AnyOwner']     ?? '') ?></td>
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

<?php
include("$root/backend/footer.php");
?>
