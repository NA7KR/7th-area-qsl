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

$title = "Cards Received";
$selectedLetter = null;

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
 * Fetch data from the database.
 *
 * @param PDO $pdo
 * @param string $tableName
 * @param array $columns
 * @return array
 */
function fetchData(PDO $pdo, $tableName, $columns)
{
    $columnList = implode(',', $columns);
    $stmt = $pdo->query("SELECT $columnList FROM $tableName");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Process raw data into a key-value array.
 *
 * @param array $rawData
 * @param array $keyIndexes
 * @return array
 */
function processData($rawData, $keyIndexes)
{
    $data = [];
    foreach ($rawData as $row) {
        $key = $row[$keyIndexes['keyName']];
        $value = $row[$keyIndexes['valueName']];
        $data[$key] = $value;
    }
    return $data;
}

/**
 * Get processed data from the database.
 *
 * @param PDO $pdo
 * @param string $tableName
 * @param array $keyIndexes
 * @return array
 */
function getData(PDO $pdo, $tableName, $keyIndexes)
{
    $rawData = fetchData($pdo, $tableName, [$keyIndexes['keyName'], $keyIndexes['valueName']]);
    if (!empty($rawData)) {
        return processData($rawData, $keyIndexes);
    }
    return [];
}

/**
 * Get the next ID from the tbl_CardRec table.
 *
 * @param PDO $pdo
 * @return int
 */
function getNextID(PDO $pdo)
{
    try {
        $stmt = $pdo->query("SELECT MAX(ID) as maxID FROM tbl_CardRec");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['maxID'] ?? 0) + 1;
    } catch (PDOException $e) {
        error_log("Error getting next ID: " . $e->getMessage());
        return 1;
    }
}

// Establish PDO connection
$pdo = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedLetter = $_POST['letter'] ?? null;
    
    if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
        $dbInfo = $config['sections'][$selectedLetter];
        $pdo = getPDOConnection($dbInfo);
        $ID = getNextID($pdo);
    }
}

?>

    <div class="center-content">
        <img src="/7thArea.png" alt="7th Area" />
        <h1 class="my-4 text-center">7th Area QSL Bureau - Cards Received</h1>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <label for="letter">Select a Section:</label>
            <select name="letter" id="letter" class="form-control">
                <option value="F" <?php echo ($selectedLetter === 'F') ? 'selected' : ''; ?>>F</option>
                <option value="O" <?php echo ($selectedLetter === 'O') ? 'selected' : ''; ?>>O</option>
            </select>
            <button type="submit">Select</button>
        </form>
        <br>
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 10px; width: 400px; padding: 10px; border: 1px solid;">
            <!-- ID -->
            <label for="ID" style="text-align: right; font-weight: bold;">ID:</label>
            <input type="text" id="ID" name="ID" required readonly class="form-control readonly"
                   value="<?php echo isset($ID) ? htmlspecialchars($ID) : ''; ?>">

            <!-- Call -->
            <label for="Call" style="text-align: right; font-weight: bold;">Call:</label>
            <input type="text" id="Call" name="Call" required class="form-control"
                   value="<?php echo isset($Call) ? htmlspecialchars($Call) : ''; ?>">

            <!-- Cards Received -->
            <label for="CardsReceived" style="text-align: right; font-weight: bold;">Cards Received:</label>
            <input type="text" id="CardsReceived" name="CardsReceived" required class="form-control"
                   value="<?php echo isset($CardsReceived) ? htmlspecialchars($CardsReceived) : ''; ?>">

            <!-- Date Received -->
            <label for="DateReceived" style="text-align: right; font-weight: bold;">Date Received:</label>
            <input type="text" id="DateReceived" name="DateReceived" required class="form-control"
                   value="<?php echo isset($DateReceived) ? htmlspecialchars($DateReceived) : ''; ?>">

            <!-- New Call -->
            <label for="NewCall" style="text-align: right; font-weight: bold;">New Call:</label>
            <input type="text" id="NewCall" name="NewCall" required class="form-control"
                   value="<?php echo isset($NewCall) ? htmlspecialchars($NewCall) : ''; ?>">

            <!-- Status -->
            <label for="Status" style="text-align: right; font-weight: bold;">Status:</label>
            <select name="status" id="status">
                <!-- Optional: A placeholder option -->
                <option value="" disabled selected>Select Status</option>

                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="New">New</option>
                <option value="Pending">Pending</option>
                <option value="Forward">Forward</option>
                <option value="QSL Mgr">QSL Mgr</option>
                <option value="Trustee">Trustee</option>
                <option value="VIA">VIA</option>
                <option value="NOTIFIED">NOTIFIED</option>
                <option value="Licensee Expired">Licensee Expired</option>
                <option value="AK Buro">AK Buro</option>
                <option value="CLUB">CLUB</option>
                <option value="Address not available">Address not available</option>
                <option value="DNU-DESTROY">DNU-DESTROY</option>
                <option value="License Expired">License Expired</option>
                <option value="Reissue">Reissue</option>
                <option value="SILENT KEY">SILENT KEY</option>
                <option value="Active_DIFF_Address">Active_DIFF_Address</option>
            </select>

            <!-- Mail-Inst -->
            <label for="Mail-Inst" style="text-align: right; font-weight: bold;">Mail-Inst:</label>
            <select id="Mail-Inst" name="Mail-Inst" required class="form-control">
                <option value="option1">Option 1</option>
                <option value="option2">Option 2</option>
                <option value="option3">Option 3</option>
            </select>
        </div>
    </div>

    

<?php
include("$root/backend/footer.php");
?>