<?php
namespace MountTracker;

use PDO;
use PDOException;

session_start();
$root = realpath($_SERVER["DOCUMENT_ROOT"]);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Mount Tracker";

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
 * Fetch all rows from tbl_Expense, optionally with date filtering on $dateColumn.
 */
function fetchAllExpenses(PDO $pdo, string $tableName, ?string $startDate = null, ?string $endDate = null, ?string $dateColumn = null): array
{
    $query = "SELECT * FROM `$tableName`";
    $params = [];

    if ($dateColumn && $startDate && $endDate) {
        $query .= " WHERE `$dateColumn` BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate']   = $endDate;
    }

    $query .= " ORDER BY `ID` ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error retrieving expense data: " . $e->getMessage());
        throw new \RuntimeException("Error retrieving expense data.");
    }
}

/**
 * Group rows by `Cost` in PHP, summing MoneyReceived/Retailer, 
 * then calculating "Total Purchased", "Cost of Postage"
 * using config-based values (if any).
 */
function parseAndAggregate(array $rows, array $config): array
{
    $aggregated = [];

    foreach ($rows as $r) {
        $cost          = $r['Cost']            ?? '';
        $moneyReceived = (float)($r['MoneyReceived']    ?? 0);
        $retailer      = (float)($r['Retailer']         ?? 0);

        $numericValue = isset($config['mounts'][$cost]) ? (float)$config['mounts'][$cost] : (float)$cost;

        if (!isset($aggregated[$cost])) {
            $aggregated[$cost] = [
                'Cost'           => $cost,
                'MoneyReceived'  => 0,
                'Retailer'       => 0,
                'Total Purchased'=> 0.0,
                'Cost of Postage'=> 0.0,
                'NumericValue'   => $numericValue
            ];
        }

        $aggregated[$cost]['MoneyReceived'] += $moneyReceived;
        $aggregated[$cost]['Retailer']      += $retailer;
    }

    foreach ($aggregated as &$mount) {
        $purchased = $mount['MoneyReceived'];
        $used      = $mount['Retailer'];
        $numeric   = $mount['NumericValue'];

        $mount['Total Purchased'] = round($purchased * $numeric, 2);
        $mount['Cost of Postage'] = round($used * $numeric, 2);
    }
    unset($mount);

    return array_values($aggregated);
}

// ----------------- MAIN -----------------
$selectedLetter   = null;
$dataRows         = [];
$aggregatedMounts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedLetter   = $_POST['letter']            ?? null;
    $enableDateFilter = isset($_POST['dateFilterCheckbox']);
    $startDate        = $_POST['startDate']         ?? null;
    $endDate          = $_POST['endDate']           ?? null;

    if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
        $dbInfo = $config['sections'][$selectedLetter];
        $pdo    = getPDOConnection($dbInfo);

        if ($enableDateFilter && $startDate && $endDate) {
            $dataRows = fetchAllExpenses($pdo, 'tbl_Expense', $startDate, $endDate, 'DateAdded');
        } else {
            $dataRows = fetchAllExpenses($pdo, 'tbl_Expense');
        }

        $aggregatedMounts = parseAndAggregate($dataRows, $config);
        usort($aggregatedMounts, function($a, $b) {
            return strcasecmp($a['Cost'], $b['Cost']);
        });
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

    <h2>Mount Summary</h2>

    <?php if (!empty($aggregatedMounts)): ?>
        <table>
            <thead>
                <tr>
                    <th>Cost</th>
                    <th>MoneyReceived</th>
                    <th>Retailer</th>
                    <th>Description</th>
                    <th>Reciept</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aggregatedMounts as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Cost']             ?? '') ?></td>
                        <td><?= htmlspecialchars($row['MoneyReceived']    ?? '') ?></td>
                        <td><?= htmlspecialchars($row['Retailer']         ?? '') ?></td>
                        <td><?= htmlspecialchars($row['Description']      ?? '') ?></td>
                        <td><?= htmlspecialchars($row['Reciept']          ?? '') ?></td>
                        <td><?= htmlspecialchars($row['Date']             ?? '') ?></td>
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
</</div>

        <button type="submit">Submit</button>
    </form>

    <h2>Mount Summary</h2>

    <?php if (!empty($aggregatedMounts)): ?>
        <table>
            <thead>
                <tr>
                    <th>Cost</th>
                    <th>MoneyReceived</th>
                    <th>Retailer</th>
                    <th>Description</th>
                    <th>Reciept</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aggregatedMounts as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Cost']             ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['MoneyReceived']    ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['Retailer']         ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['Description']      ?? 'N/A') ?></td>
                        <td>
                            <?php if (!empty($row['Reciept'])): ?>
                                <a href="<?= htmlspecialchars($row['Reciept']) ?>" target="_blank">View Reciept</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['Date']             ?? 'N/A') ?></td>
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
?></div>

        <button type="submit">Submit</button>
    </form>

    <h2>Mount Summary</h2>

    <?php if (!empty($aggregatedMounts)): ?>
        <table>
            <thead>
                <tr>
                    <th>Cost</th>
                    <th>MoneyReceived</th>
                    <th>Retailer</th>
                    <th>Description</th>
                    <th>Reciept</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aggregatedMounts as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Cost']             ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['MoneyReceived']    ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['Retailer']         ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['Description']      ?? 'N/A') ?></td>
                        <td>
                            <?php if (!empty($row['Reciept'])): ?>
                                <a href="<?= htmlspecialchars($row['Reciept']) ?>" target="_blank">View Reciept</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['Date']             ?? 'N/A') ?></td>
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
?>script>

<?php
include("$root/backend/footer.php");
?>
