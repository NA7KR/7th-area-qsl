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
print_r($_POST);

session_start();
$root = realpath($_SERVER["DOCUMENT_ROOT"]);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Cards To Mail";
$selectedLetter = null;
$ID = null;
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
        $stmt = $pdo->query("SELECT MAX(ID) as maxID FROM tbl_CardM");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['maxID'] ?? 0) + 1;
    } catch (PDOException $e) {
        error_log("Error getting next ID: " . $e->getMessage());
        return 1;
    }
}

// If the user submitted the form to select a letter:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter_select_form'])) {
    $selectedLetter = $_POST['letter'] ?? null;
    
    if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
        $dbInfo = $config['sections'][$selectedLetter];
        $pdo = getPDOConnection($dbInfo);
        $ID = getNextID($pdo);
    }
}
?>
<div class="center-content">
    <h1 class="my-4 text-center">7th Area QSL Bureau - Cards Mailed</h1>
    <!-- 1) Form for selecting the section (letter) -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div style="display: flex; align-items: center; gap: 10px; width: 100%; justify-content: space-between;">
            <input type="hidden" name="letter_select_form" value="1">
            <label for="letter" style="white-space: nowrap;">Select a Section:</label>
            <div style="flex-grow: 1; display: flex; justify-content: flex-end;">
                <select name="letter" id="letter" class="form-control" style="width: 150px; height: 35px; font-size: 16px; appearance: auto;">
                    <?php if (isset($config['sections']) && is_array($config['sections'])): ?>
                        <?php foreach ($config['sections'] as $letter => $dbInfo): ?>
                            <option value="<?= htmlspecialchars($letter) ?>"
                                    <?= $selectedLetter === $letter ? 'selected' : '' ?>>
                                <?= htmlspecialchars($letter) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">No sections available</option>
                    <?php endif; ?>
                </select>
            </div>
            <?php
            // Add this line to make the ID available to JavaScript
            echo "<script>window.nextID = '$ID';</script>";
            ?>
            <button type="submit" style="height: 35px; padding: 0 15px; line-height: 35px; text-align: center;">Select</button>
        </div>
    </form>
    
    <div style="display: grid; grid-template-columns: auto 1fr; gap: 10px; width: 400px; padding: 10px; border: 1px solid;">
        <!-- ID -->
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <label for="ID" style="text-align: right; font-weight: bold;">ID:</label>
            <label><?php echo isset($ID) ? htmlspecialchars($ID) : ''; ?></label>
        </div>

        <!-- Call -->
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <label for="Call" style="text-align: right; font-weight: bold;">Call:</label>
            <input
                type="text"
                id="Call"
                name="Call"
                required
                class="form-control"
                value="<?php echo isset($Call) ? htmlspecialchars($Call) : ''; ?>"
                style="flex: 1;"
            >
        </div>
        <!-- Cards To Mail -->
        <label for="CardsToMail" style="text-align: right; font-weight: bold;">Cards To Mail:</label>
        <input
            type="text"
            id="CardsToMail"
            name="CardsToMail"
            required
            class="form-control"
            value="<?php echo isset($CardsToMail) ? htmlspecialchars($CardsToMail) : ''; ?>"
        >
        <!-- Weight -->
        <label  style="text-align: right; font-weight: bold;">Weight:</label>
        <input
            type="text"
            id="weight"
            name="weight"
            required
            class="form-control"
            value="<?php echo isset($Weight) ? htmlspecialchars($Weight) : ''; ?>"
        >
          <!-- Postage Cost -->
          <label  style="text-align: right; font-weight: bold;">Postage Cost:</label>
          <input
                type="text"
                id="PostageCost"
                name="PostageCost"
                required
                class="form-control"
                value="<?php echo isset($PostageCost) ? htmlspecialchars($PostageCost) : ''; ?>"
            >

          <!-- Other Cost -->
          <label for="OtherCost" style="text-align: right; font-weight: bold;">Other Cost:</label>
        <input
            type="text"
            id="OtherCost"
            name="OtherCost"
            required
            class="form-control"
            value="<?php echo isset($OtherCost) ? htmlspecialchars($OtherCost) : ''; ?>"
        >
          <!-- Total Cost -->
          <label for="Total Cost" style="text-align: right; font-weight: bold;">Total Cost:</label>
        <input
            type="text"
            id="TotalCost"
            name="TotalCost"
            required
            readonly
            class="form-control"
            value="<?php echo isset($TotalCost) ? htmlspecialchars($TotalCost) : ''; ?>"
        >
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <!-- Status (Label) -->
            <label style="text-align: right; font-weight: bold;">Status:</label>
            <label id="Status"><?php echo isset($Status) ? htmlspecialchars($Status) : 'N/A'; ?></label>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <!-- Mail-Inst (Label) -->
            <label style="text-align: right; font-weight: bold;">Mail:</label>
            <label id="Mail-Inst"><?php echo isset($MailInst) ? htmlspecialchars($MailInst) : 'N/A'; ?></label>
        </div>
        <!-- Cards On Hand -->
        <label style="text-align: right; font-weight: bold;">Cards On Hand:</label>
        <label id="CardsOnHand"><?php echo isset($CardsOnHand) ? htmlspecialchars($CardsOnHand) : '0'; ?></label>

        <!-- Account Balance (Label) -->
        <label style="text-align: right; font-weight: bold;">Account Balance:</label>
        <label id="AccountBalance" style="color: 
            <?php echo isset($AccountBalance) && $AccountBalance > 0.85 ? 'green' : 'red'; ?>">
            <?php echo isset($AccountBalance) ? htmlspecialchars($AccountBalance) : '0'; ?>
        </label>
        
    </div>
     
     
    <!-- 3) Second form to submit letter, call, CardsReceived to a new page -->
<form method="POST" action="../backend/stamps.php" style="margin-top: 20px;" id="submitForm">
    <input type="hidden" name="letter" id="hiddenLetter" value="<?php echo htmlspecialchars($selectedLetter ?? ''); ?>" />
    <input type="hidden" name="ID" value="<?php echo htmlspecialchars($ID ?? ''); ?>" />
    <input type="hidden" name="Call" value="<?php echo htmlspecialchars($Call ?? ''); ?>" />
    <input type="hidden" name="CardsToMail" value="<?php echo htmlspecialchars($CardsToMail ?? ''); ?>" />
    <input type="hidden" name="weight" value="<?php echo htmlspecialchars($Weight ?? ''); ?>" />
    <input type="hidden" name="PostageCost" value="<?php echo htmlspecialchars($PostageCost ?? ''); ?>" />
    <input type="hidden" name="OtherCost" value="<?php echo htmlspecialchars($OtherCost ?? ''); ?>" />
    <input type="hidden" name="TotalCost" value="<?php echo htmlspecialchars($TotalCost ?? ''); ?>" />
    <button type="submit" id="submitCardButton" style="padding: 6px 12px;" disabled>Submit to Add Card</button>
</form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all input elements
    const callInput = document.getElementById('Call');
    const letterSelect = document.getElementById('letter');
    const letterForm = document.querySelector('form');
    const statusLabel = document.getElementById('Status');
    const submitCardButton = document.getElementById('submitCardButton');
    const cardsToMailInput = document.getElementById('CardsToMail');
    const weightInput = document.getElementById('weight');
    const postageCostInput = document.getElementById('PostageCost');
    const otherCostInput = document.getElementById('OtherCost');
    const totalCostInput = document.getElementById('TotalCost');
    const cardsOnHandLabel = document.getElementById('CardsOnHand');
    const mailInstLabel = document.getElementById('Mail-Inst');
    const accountBalanceLabel = document.getElementById('AccountBalance');
    const submitForm = document.getElementById('submitForm');

    // Standardized error text for missing data
    const missingDataText = 'Data not found';

    // Initially disable all inputs
    function initializeInputs() {
        callInput.disabled = true;
        cardsToMailInput.disabled = true;
        weightInput.disabled = true;
        postageCostInput.disabled = true;
        otherCostInput.disabled = true;
        totalCostInput.disabled = true;
    }

    // Call initialization
    initializeInputs();

    // Add observer for ID changes
    const idObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (window.nextID) {
                callInput.disabled = false;
            }
        });
    });

    // Start observing
    idObserver.observe(document.documentElement, {
        childList: true,
        subtree: true
    });

    // Form submit handler
    letterForm.addEventListener('submit', function() {
        setTimeout(() => {
            if (window.nextID) {
                callInput.disabled = false;
            }
        }, 100);
    });

    // Check on page load
    if (window.nextID) {
        callInput.disabled = false;
    }

    // Enable/disable fields based on Call input
    callInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        cardsToMailInput.disabled = !hasValue;
        
        if (!hasValue) {
            cardsToMailInput.disabled = true;
            weightInput.disabled = true;
            postageCostInput.disabled = true;
            otherCostInput.disabled = true;
            totalCostInput.disabled = true;
        }
    });

    // Enable/disable fields based on Cards to Mail input
    cardsToMailInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        weightInput.disabled = !hasValue;
        
        if (!hasValue) {
            postageCostInput.disabled = true;
            otherCostInput.disabled = true;
            totalCostInput.disabled = true;
        }
    });

    // Enable/disable fields based on Weight input
    weightInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        postageCostInput.disabled = !hasValue;
        
        if (!hasValue) {
            otherCostInput.disabled = true;
            totalCostInput.disabled = true;
        }
    });

    // Enable/disable fields based on Postage Cost input
    postageCostInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        otherCostInput.disabled = !hasValue;
        
        if (!hasValue) {
            totalCostInput.disabled = true;
        }
        calculateTotal();
    });

    // Enable/disable Total Cost based on Other Cost input
    otherCostInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        totalCostInput.disabled = !hasValue;
        calculateTotal();
    });

    // Calculate Total Cost
    function calculateTotal() {
        const postageCost = parseFloat(postageCostInput.value) || 0;
        const otherCost = parseFloat(otherCostInput.value) || 0;
        totalCostInput.value = (postageCost + otherCost).toFixed(2);
    }

    // Validate numeric input
    function validateNumericInput(event) {
        const value = event.target.value;
        if (!/^\d*\.?\d*$/.test(value)) {
            event.target.value = value.replace(/[^\d.]/g, '');
        }
        if ((value.match(/\./g) || []).length > 1) {
            event.target.value = value.replace(/\.+$/, '');
        }
    }

    // Apply numeric validation
    cardsToMailInput.addEventListener('input', validateNumericInput);
    weightInput.addEventListener('input', validateNumericInput);
    postageCostInput.addEventListener('input', validateNumericInput);
    otherCostInput.addEventListener('input', validateNumericInput);

    // Normalize status text for comparison
    function normalizeStatus(status) {
        return status.trim().replace(/\.$/, "");
    }

    // Toggle the Submit Button
    function toggleSubmitButton() {
        const invalidStatuses = ["No status found", "Enter a call sign", "N/A", missingDataText];
        const status = normalizeStatus(statusLabel.textContent);
        submitCardButton.disabled = invalidStatuses.includes(status);
    }

    // Update label with standardized message
    function updateLabel(label, url, params) {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(params)
        })
        .then(response => {
            if (!response.ok) throw new Error(`Error: ${response.statusText}`);
            return response.text();
        })
        .then(text => {
            label.textContent = text.trim() || missingDataText;
            if (label === statusLabel) {
                toggleSubmitButton();
            }
        })
        .catch(error => {
            console.error(`Error loading data for ${label.id}:`, error);
            label.textContent = missingDataText;
            if (label === statusLabel) {
                toggleSubmitButton();
            }
        });
    }

    // Update Status Label
    function updateStatusLabel() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            statusLabel.textContent = 'Enter a call sign';
            toggleSubmitButton();
            return;
        }

        updateLabel(statusLabel, '../backend/fetch_status.php', { letter, call });
    }

    // Update Mail-Inst Label
    function updateMailInstLabel() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            mailInstLabel.textContent = 'Enter a call sign';
            return;
        }

        updateLabel(mailInstLabel, '../backend/fetch_mail_inst.php', { letter, call });
    }

    // Update Account Balance Label
    function updateAccountBalanceLabel() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            accountBalanceLabel.textContent = 'Enter a call sign';
            return;
        }

        updateLabel(accountBalanceLabel, '../backend/fetch_account_balance.php', { letter, call });
    }

    // Update Cards On Hand Label
    function updateCardsOnHandLabel() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            cardsOnHandLabel.textContent = 'Enter a call sign';
            return;
        }

        updateLabel(cardsOnHandLabel, '../backend/fetch_cards_on_hand.php', { letter, call });
    }

    // Attach blur event listener to Call input
    callInput.addEventListener('blur', function() {
        updateStatusLabel();
        updateMailInstLabel();
        updateAccountBalanceLabel();
        updateCardsOnHandLabel();
    });

    // Handle popup window
    postageCostInput.addEventListener('click', function() {
        const letter = letterSelect.value;
        const weight = weightInput.value;
        if (weight) {
            openPopupWindow(letter, weight);
        }
    });

    // Populate hidden fields before form submission
    submitForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        document.querySelector('input[name="Call"]').value = callInput.value;
        document.querySelector('input[name="CardsToMail"]').value = cardsToMailInput.value;
        document.querySelector('input[name="weight"]').value = weightInput.value;
        document.querySelector('input[name="PostageCost"]').value = postageCostInput.value;
        document.querySelector('input[name="OtherCost"]').value = otherCostInput.value;
        document.querySelector('input[name="TotalCost"]').value = totalCostInput.value;

        // Debugging: Log the values to the console
        console.log('Call:', callInput.value);
        console.log('CardsToMail:', cardsToMailInput.value);
        console.log('Weight:', weightInput.value);
        console.log('PostageCost:', postageCostInput.value);
        console.log('OtherCost:', otherCostInput.value);
        console.log('TotalCost:', totalCostInput.value);

        submitForm.submit(); // Submit the form programmatically
    });

    // Initialize button state on page load
    toggleSubmitButton();
});
</script>

