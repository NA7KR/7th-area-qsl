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

// If the user submitted the form to select a letter:
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
            <!-- Add more letters as needed -->
        </select>
        <button type="submit">Select</button>
    </form>
    <br>

    <div style="display: grid; grid-template-columns: auto 1fr; gap: 10px; width: 400px; padding: 10px; border: 1px solid;">
        <!-- ID -->
        <label for="ID" style="text-align: right; font-weight: bold;">ID:</label>
        <input
            type="text"
            id="ID"
            name="ID"
            required
            readonly
            class="form-control readonly"
            value="<?php echo isset($ID) ? htmlspecialchars($ID) : ''; ?>"
        >

        <!-- Call -->
        <label for="Call" style="text-align: right; font-weight: bold;">Call:</label>
        <input
            type="text"
            id="Call"
            name="Call"
            required
            class="form-control"
            value="<?php echo isset($Call) ? htmlspecialchars($Call) : ''; ?>"
        >

        <!-- Cards Received -->
        <label for="CardsReceived" style="text-align: right; font-weight: bold;">Cards Received:</label>
        <input
            type="text"
            id="CardsReceived"
            name="CardsReceived"
            required
            class="form-control"
            value="<?php echo isset($CardsReceived) ? htmlspecialchars($CardsReceived) : ''; ?>"
        >

        <!-- Date Received -->
        <label for="DateReceived" style="text-align: right; font-weight: bold;">Date Received:</label>
        <input
            type="date"
            id="DateReceived"
            name="DateReceived"
            required
            class="form-control"
            value="<?php echo isset($DateReceived) ? htmlspecialchars($DateReceived) : date('Y-m-d'); ?>"
        >

        <!-- Status (readonly) -->
        <label for="Status" style="text-align: right; font-weight: bold;">Status:</label>
        <select
            id="Status"
            name="Status"
            required
            readonly
            class="form-control"
        >
            <!-- Options will be inserted by fetch from fetch_status.php -->
        </select>

        <!-- Mail-Inst (readonly) -->
        <label for="Mail-Inst" style="text-align: right; font-weight: bold;">Mail-Inst:</label>
        <select
            id="Mail-Inst"
            name="Mail-Inst"
            required
            readonly
            class="form-control"
        >
            <!-- Options will be inserted by fetch from fetch_mail_inst.php -->
        </select>

        <!-- Account Balance (readonly) -->
        <label for="AccountBalance" style="text-align: right; font-weight: bold;">Account Balance:</label>
        <input
            type="text"
            id="AccountBalance"
            name="AccountBalance"
            required
            readonly
            class="form-control"
            value="<?php echo isset($AccountBalancee) ? htmlspecialchars($AccountBalancee) : ''; ?>"
        >
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const callInput         = document.getElementById('Call');
    const letterSelect      = document.getElementById('letter');
    const statusDropdown    = document.getElementById('Status');
    const mailInstDropdown  = document.getElementById('Mail-Inst');
    const accountBalanceBox = document.getElementById('AccountBalance');

    // 1) Update Status
    async function updateStatusDropdown() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            statusDropdown.innerHTML = '<option>Enter a call sign</option>';
            return;
        }

        try {
            const response = await fetch('fetch_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `letter=${encodeURIComponent(letter)}&call=${encodeURIComponent(call)}`
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.statusText}`);
            }

            // fetch_status.php returns raw <option> tags
            const optionHtml = await response.text();
            statusDropdown.innerHTML = optionHtml;
        } catch (error) {
            console.error('Failed to load status:', error);
            statusDropdown.innerHTML = '<option>Error loading Status</option>';
        }
    }

    // 2) Update Mail-Inst
    async function updateMailInstDropdown() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            mailInstDropdown.innerHTML = '<option>Enter a call sign</option>';
            return;
        }

        try {
            const response = await fetch('fetch_mail_inst.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `letter=${encodeURIComponent(letter)}&call=${encodeURIComponent(call)}`
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.statusText}`);
            }

            // fetch_mail_inst.php returns raw <option> tags
            const optionHtml = await response.text();
            mailInstDropdown.innerHTML = optionHtml;
        } catch (error) {
            console.error('Failed to load mail instructions:', error);
            mailInstDropdown.innerHTML = '<option>Error loading Mail-Inst</option>';
        }
    }

    // 3) Update AccountBalance
    async function updateAccountBalance() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            // If no call is entered, skip the request
            accountBalanceBox.value = ''; 
            return;
        }

        try {
            const response = await fetch('fetch_account_balance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `letter=${encodeURIComponent(letter)}&call=${encodeURIComponent(call)}`
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.statusText}`);
            }

            // fetch_account_balance.php should return just a text (the numeric or string balance)
            const balanceText = await response.text();
            accountBalanceBox.value = balanceText;

        } catch (error) {
            console.error('Failed to load account balance:', error);
            accountBalanceBox.value = 'Error';
        }
    }

    // Trigger all updates on "blur" of the Call field
    callInput.addEventListener('blur', () => {
        updateStatusDropdown();
        updateMailInstDropdown();
        updateAccountBalance();  // <-- add the new call here
    });
});
</script>

<?php include("$root/backend/footer.php"); ?>
