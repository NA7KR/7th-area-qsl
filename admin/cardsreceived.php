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
$title = "Cards Received";
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include("$root/backend/header.php");
$selectedLetter = "";
// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
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
            <button type="submit" style="height: 35px; padding: 0 15px; line-height: 35px; text-align: center;">Select</button>
        </div>
    </form>

    <!-- 2) Data entry fields -->
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
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <!-- Status (Label) -->
            <label for="Status" style="text-align: right; font-weight: bold;">Status:</label>
            <label id="Status"><?php echo isset($Status) ? htmlspecialchars($Status) : 'N/A'; ?></label>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <!-- Mail-Inst (Label) -->
            <label for="Mail-Inst" style="text-align: right; font-weight: bold;">Mail-Inst:</label>
            <label id="Mail-Inst"><?php echo isset($MailInst) ? htmlspecialchars($MailInst) : 'N/A'; ?></label>
        </div>

        <!-- Account Balance (Label) -->
        <label for="AccountBalance" style="text-align: right; font-weight: bold;">Account Balance:</label>
        <label id="AccountBalance"><?php echo isset($AccountBalance) ? htmlspecialchars($AccountBalance) : 'N/A'; ?></label>
    </div>

    <!-- 3) Second form to submit letter, call, CardsReceived to a new page -->
    <form method="POST" action="../backend/submit_cards.php" style="margin-top: 20px;">
    <input type="hidden" name="letter" id="hiddenLetter" value="<?php echo htmlspecialchars($selectedLetter ?? ''); ?>" />
    <input type="hidden" name="call" id="hiddenCall" value="">
    <input type="hidden" name="CardsReceived" id="hiddenCardsReceived" value="">
    <button type="submit" id="submitCardButton" style="padding: 6px 12px;" disabled>Submit to Add Card</button>
</form>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const callInput = document.getElementById('Call');
    const letterSelect = document.getElementById('letter');
    const statusLabel = document.getElementById('Status');
    const submitCardButton = document.getElementById('submitCardButton');

    // Normalize the status text for comparison
    function normalizeStatus(status) {
        return status.trim().replace(/\.$/, ""); // Trim spaces and remove trailing period
    }

    // Toggle the Submit Button
    function toggleSubmitButton() {
        const invalidStatuses = ["No status found", "Enter a call sign", "N/A"];
        const status = normalizeStatus(statusLabel.textContent);
        console.log(`Debug: Normalized Status = "${status}"`); // Debugging: Log the normalized status

        // Disable button if status matches invalid statuses
        submitCardButton.disabled = invalidStatuses.includes(status);
    }

    // Update the Status Label
    async function updateStatusLabel() {
        const call = callInput.value.trim(); // Remove extra spaces
        const letter = letterSelect.value;

        if (!call) {
            statusLabel.textContent = 'Enter a call sign';
            toggleSubmitButton(); // Ensure button is toggled after update
            return;
        }

        try {
            const response = await fetch('../backend/fetch_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `letter=${encodeURIComponent(letter)}&call=${encodeURIComponent(call)}`
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.statusText}`);
            }

            const statusText = await response.text();
            statusLabel.textContent = statusText.trim(); // Set trimmed status to the label
        } catch (error) {
            console.error('Error loading status:', error);
            statusLabel.textContent = 'Error loading Status';
        }

        toggleSubmitButton(); // Call toggleSubmitButton after updating the status
    }

    // Update the Mail-Inst Label
    async function updateMailInstLabel() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            document.getElementById('Mail-Inst').textContent = 'Enter a call sign';
            return;
        }

        try {
            const response = await fetch('../backend/fetch_mail_inst.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `letter=${encodeURIComponent(letter)}&call=${encodeURIComponent(call)}`
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.statusText}`);
            }

            const mailInstText = await response.text();
            document.getElementById('Mail-Inst').textContent = mailInstText.trim();
        } catch (error) {
            console.error('Error loading mail instructions:', error);
            document.getElementById('Mail-Inst').textContent = 'Error loading Mail-Inst';
        }
    }

    // Update the Account Balance Label
    async function updateAccountBalanceLabel() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            document.getElementById('AccountBalance').textContent = '';
            return;
        }

        try {
            const response = await fetch('../backend/fetch_account_balance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `letter=${encodeURIComponent(letter)}&call=${encodeURIComponent(call)}`
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.statusText}`);
            }

            const balanceText = await response.text();
            document.getElementById('AccountBalance').textContent = balanceText.trim();
        } catch (error) {
            console.error('Error loading account balance:', error);
            document.getElementById('AccountBalance').textContent = 'Error';
        }
    }

    // Attach Event Listeners
    callInput.addEventListener('blur', () => {
        updateStatusLabel(); // Update the status label on blur
        updateMailInstLabel(); // Update mail instructions on blur
        updateAccountBalanceLabel(); // Update account balance on blur
    });

    // Initialize button state on page load
    toggleSubmitButton();
});
</script>



<?php include("$root/backend/footer.php"); ?>
