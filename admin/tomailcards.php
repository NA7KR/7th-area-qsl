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
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include("$root/backend/header.php");



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
</div>  
  
<div class="center-content">
    <!-- 1) Form for selecting the section (letter) -->
    <form method="POST" action="../backend/mailstamps.php" style="margin-top: 10px;" id="submitForm">
        <div style="display: grid; grid-template-columns: auto 1fr;center; gap: 5px; width: 100%; justify-content: space-between;border: 1px solid;">
            <label for="ID" style="text-align: right; font-weight: bold; width: 150px">ID:</label>
            <div style="flex-grow: 1; display: flex; justify-content: flex-end;">
                <!-- ID -->
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; width: 150px; height: 35px; font-size: 16px">  
                    <label><?php echo isset($ID) ? htmlspecialchars($ID) : ''; ?></label>
                </div>
            </div>
            <!-- Call -->
            <label for="Call" style="text-align: right; font-weight: bold;nowrap;">Call:</label>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <input
                    type="text"
                    id="Call"
                    name="Call"
                    required
                    class="form-control"
                    value="<?php echo isset($Call) ? htmlspecialchars($Call) : ''; ?>"
                    style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; width: 150px; height: 35px; font-size: 16px"
                >
            </div>
            <!-- Cards To Mail -->
            <label for="CardsToMail" style="text-align: right; font-weight: bold; nowrap;">Cards To Mail:</label>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <input
                    type="text"
                    id="CardsToMail"
                    name="CardsToMail"
                    required
                    class="form-control"
                    value="<?php echo isset($CardsToMail) ? htmlspecialchars($CardsToMail) : ''; ?>"
                    style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; width: 150px; height: 35px; font-size: 16px"
                >
            </div>
            <!-- Weight -->
            <label style="text-align: right; font-weight: bold;">Weight:</label>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <input
                    type="text"
                    id="weight"
                    name="weight"
                    required
                    class="form-control"
                    value="<?php echo isset($Weight) ? htmlspecialchars($Weight) : ''; ?>"
                    style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; width: 150px; height: 35px; font-size: 16px"
                >
            </div>
            <!-- Postage Cost -->
            <label style="text-align: right; font-weight: bold;">Postage Cost:</label>
            <div style="display: flex; align-items: center; gap: 1px; margin-bottom: 10px;">
                <input
                    type="text"
                    id="PostageCost"
                    name="PostageCost"
                    required
                    class="form-control"
                    value="<?php echo isset($PostageCost) ? htmlspecialchars($PostageCost) : ''; ?>"
                    style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; width: 150px; height: 35px; font-size: 16px"
                >
            </div>
            
            <!-- Other Cost -->
            <label for="OtherCost" style="text-align: right; font-weight: bold;">Other Cost:</label>
            <div style="display: flex; align-items: center; gap: 1px; margin-bottom: 10px;">
            <input
                type="text"
                id="OtherCost"
                name="OtherCost"
                required
                class="form-control"
                value="<?php echo isset($OtherCost) ? htmlspecialchars($OtherCost) : ''; ?>"
                style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; width: 150px; height: 35px; font-size: 16px"
                >
            </div>
            <!-- Total Cost -->
            <label for="Total Cost" style="text-align: right; font-weight: bold;">Total Cost:</label>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <input
                    type="text"
                    id="TotalCost"
                    name="TotalCost"
                    required
                    readonly
                    class="form-control"
                    value="<?php echo isset($TotalCost) ? htmlspecialchars($TotalCost) : ''; ?>"
                    style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; width: 150px; height: 35px; font-size: 16px"
                >
            </div>
               
            <!-- Status (Label) -->
            <label style="text-align: right; font-weight: bold;">Status:</label>
            <label id="Status"><?php echo isset($Status) ? htmlspecialchars($Status) : 'N/A'; ?></label>

            <!-- Mail-Inst (Label) -->
            <label style="text-align: right; font-weight: bold;">Mail:</label>
            <label id="Mail-Inst"><?php echo isset($MailInst) ? htmlspecialchars($MailInst) : 'N/A'; ?></label>

            <!-- Cards On Hand -->
            <label style="text-align: right; font-weight: bold;">Cards On Hand:</label>
            <label id="CardsOnHand"><?php echo isset($CardsOnHand) ? htmlspecialchars($CardsOnHand) : '0'; ?></label>

            <!-- Account Balance (Label) -->
            <label style="text-align: right; font-weight: bold;">Account Balance:</label>
            <label id="AccountBalance" style="color: 
            <?php echo isset($AccountBalance) && $AccountBalance > 0.85 ? 'green' : 'red'; ?>">
            <?php echo isset($AccountBalance) ? htmlspecialchars($AccountBalance) : '0'; ?>
            </label>    

            <!-- Hidden fields -->
            <input type="hidden" name="letter" id="hiddenLetter" value="<?php echo htmlspecialchars($selectedLetter ?? ''); ?>" />
            <input type="hidden" name="ID" value="<?php echo htmlspecialchars($ID ?? ''); ?>" />
            <button type="submit" id="submitCardButton" style="padding: 6px 12px; grid-column: 1 / span 2;" disabled>Submit to Add Card</button>
        </div>
    </form>
</div> 

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all input elements
    const callInput = document.getElementById('Call');
    const letterSelect = document.getElementById('letter');
    const letterForm = document.querySelector('form');
    const cardsToMailInput = document.getElementById('CardsToMail');
    const weightInput = document.getElementById('weight');
    const postageCostInput = document.getElementById('PostageCost');
    const otherCostInput = document.getElementById('OtherCost');
    const totalCostInput = document.getElementById('TotalCost');
    const submitCardButton = document.getElementById('submitCardButton');
    const statusLabel = document.getElementById('Status');
    const mailInstLabel = document.getElementById('Mail-Inst');
    const accountBalanceLabel = document.getElementById('AccountBalance');
    const cardsOnHandLabel = document.getElementById('CardsOnHand');
    const submitForm = document.querySelector('form:not([name="letter_select_form"])');
    const missingDataText = 'No data available';
    
    // Initially disable call input
    callInput.disabled = true;
    
    // Initially disable all inputs
    callInput.disabled = true;
    cardsToMailInput.disabled = true;
    weightInput.disabled = true;
    postageCostInput.disabled = true;
    otherCostInput.disabled = true;
    totalCostInput.disabled = true;

    // Handle the letter selection form submission
    letterForm.addEventListener('submit', function(event) {
        // Only handle the letter select form
        if (!event.target.querySelector('input[name="letter_select_form"]')) {
            return;
        }
        
        // Don't disable the call input after submitting the letter selection
        event.preventDefault();
        
        // Submit the form using fetch or similar to avoid page reload
        const formData = new FormData(event.target);
        fetch(event.target.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            // Enable the call input after letter is selected
            callInput.disabled = false;
        });
    });

    // Enable call input if ID exists on page load
    if (window.nextID) {
        callInput.disabled = false;
    }

    // Enable/disable fields based on Call input
    callInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        cardsToMailInput.disabled = !hasValue;
        
        // If Call is empty, disable all subsequent fields
        if (!hasValue) {
            cardsToMailInput.value = '';
            weightInput.value = '';
            postageCostInput.value = '';
            totalCostInput.value = '';
            otherCostInput.disabled = true;
            weightInput.disabled = true;
            postageCostInput.disabled = true;
            totalCostInput.disabled = true;
        }
    });

    // Enable/disable fields based on Cards to Mail input
    cardsToMailInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        weightInput.disabled = !hasValue;
        
        // If Cards to Mail is empty, disable and clear subsequent fields
        if (!hasValue) {
            weightInput.value = '';
            postageCostInput.value = '';
            totalCostInput.value = '';
            otherCostInpu
            postageCostInput.disabled = true;
            totalCostInput.disabled = true;
        }
    });

    // Enable/disable fields based on Weight input
    weightInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        postageCostInput.disabled = !hasValue;
        
        // If Weight is empty, disable and clear subsequent fields
        if (!hasValue) {
            postageCostInput.value = '';
            totalCostInput.value = '';
            totalCostInput.disabled = true;
        }
    });

    // Enable/disable fields based on Postage Cost input
    postageCostInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        otherCostInput.disabled = !hasValue;
        
        // If Postage Cost is empty, clear total
        if (!hasValue) {
            otherCostInput.value = '';
        }
        calculateTotal();
    });

    // Calculate Total Cost
    function calculateTotal() {
        const postageCost = parseFloat(postageCostInput.value) || 0;
        const otherCost = parseFloat(otherCostInput.value) || 0;
        totalCostInput.value = (postageCost + otherCost).toFixed(2);
    }

    postageCostInput.addEventListener('input', calculateTotal);
    otherCostInput.addEventListener('input', calculateTotal);
    // Enable/disable fields based on Postage Cost input
    postageCostInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        otherCostInput.disabled = !hasValue;
        totalCostInput.disabled = !hasValue;
        
        if (!hasValue) {
            otherCostInput.value = '';
            totalCostInput.value = '';
        }
        calculateTotal();
    });

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

    // Handle popup window for postage cost
    postageCostInput.addEventListener('click', function() {
        const letter = letterSelect.value;
        const weight = weightInput.value;
        if (weight) {
            openPopupWindow(letter, weight);
        }
    });

    // Populate hidden fields before form submission
    submitForm.addEventListener('submit', function(event) {
        event.preventDefault();
        document.querySelector('input[name="Call"]').value = callInput.value;
        document.querySelector('input[name="CardsToMail"]').value = cardsToMailInput.value;
        document.querySelector('input[name="weight"]').value = weightInput.value;
        document.querySelector('input[name="PostageCost"]').value = postageCostInput.value;
        document.querySelector('input[name="TotalCost"]').value = totalCostInput.value;

        // Debugging: Log the values to the console
        console.log('Call:', callInput.value);
        console.log('CardsToMail:', cardsToMailInput.value);
        console.log('Weight:', weightInput.value);
        console.log('PostageCost:', postageCostInput.value);
        console.log('TotalCost:', totalCostInput.value);

        submitForm.submit();
    });

    // Initialize button state on page load
    toggleSubmitButton();
});
</script>

<?php
include("$root/backend/footer.php");
?>

