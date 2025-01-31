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

#############
$configdb = include("../config.php");
include_once("../backend/allow.php");
$call = strtoupper(htmlspecialchars($_SESSION['username']?? ''));
$status = getStatusByCallAndLetter($call, $selectedLetter, $configdb);
###########
?>
<div class="center-content">
    <h1 class="my-4 text-center">7th Area QSL Bureau - Cards Mailed</h1>

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

    <div class="center-content">
    <h1 class="my-4 text-center">7th Area QSL Bureau - Cards Mailed</h1>

    <div style="display: flex; width: 100%; gap: 20px;">

        <div style="flex: 1;">
            <?php
         if (array_key_exists($selectedLetter, $config['sections'])) {

        $dbInfo = $config['sections'][$selectedLetter];
        $pdo = getPDOConnection($dbInfo);

        $dataRows = fetchAllStamps($pdo, 'tbl_Stamps');

        $aggregatedStamps = parseAndAggregate($dataRows, $config);
         
        usort($aggregatedStamps, function ($a, $b) {
            return strcasecmp($a['Value'], $b['Value']);
        
        });
    }
        ?>
            <form method="POST" action="../backend/mailstamps.php" style="margin-top: 10px;" id="submitForm">
                <div style="display: grid; grid-template-columns: auto 1fr; gap: 5px; width: 100%; border: 1px solid; padding: 10px;">  <label for="ID" style="text-align: right; font-weight: bold; width: 150px">ID:</label>
                    <label style="width: 150px; height: 35px; font-size: 16px"><?php echo isset($ID) ? htmlspecialchars($ID) : ''; ?></label>

                    <label for="Call" style="text-align: right; font-weight: bold; nowrap;">Call:</label>
                    <input type="text" id="Call" name="Call" required class="form-control"
                           value="<?php echo isset($Call) ? htmlspecialchars($Call) : ''; ?>"
                           <?php if ($status != 'Edit') { echo 'disabled'; } ?>
                           style="width: 150px; height: 35px; font-size: 16px">

                    <label for="CardsToMail" style="text-align: right; font-weight: bold; nowrap;">Cards To Mail:</label>
                    <input type="text" id="CardsToMail" name="CardsToMail" required class="form-control"
                           value="<?php echo isset($CardsToMail) ? htmlspecialchars($CardsToMail) : ''; ?>"
                           style="width: 150px; height: 35px; font-size: 16px">

                    <label style="text-align: right; font-weight: bold;">Weight:</label>
                    <input type="text" id="weight" name="weight" required class="form-control"
                           value="<?php echo isset($Weight) ? htmlspecialchars($Weight) : ''; ?>"
                           style="width: 150px; height: 35px; font-size: 16px">

                    <label style="text-align: right; font-weight: bold;">Postage Cost:</label>
                    <input type="text" id="PostageCost" name="PostageCost" required class="form-control"
                           value="<?php echo isset($PostageCost) ? htmlspecialchars($PostageCost) : ''; ?>"
                           style="width: 150px; height: 35px; font-size: 16px">

                    <label for="OtherCost" style="text-align: right; font-weight: bold;">Other Cost:</label>
                    <input type="text" id="OtherCost" name="OtherCost" required class="form-control"
                           value="<?php echo isset($OtherCost) ? htmlspecialchars($OtherCost) : ''; ?>"
                           style="width: 150px; height: 35px; font-size: 16px">

                    <label for="Total Cost" style="text-align: right; font-weight: bold;">Total Cost:</label>
                    <input type="text" id="TotalCost" name="TotalCost" required readonly class="form-control"
                           value="<?php echo isset($TotalCost) ? htmlspecialchars($TotalCost) : ''; ?>"
                           style="width: 150px; height: 35px; font-size: 16px">

                    <label style="text-align: right; font-weight: bold;">Status:</label>
                    <label id="Status"><?php echo isset($Status) ? htmlspecialchars($Status) : 'N/A'; ?></label>

                    <label style="text-align: right; font-weight: bold;">Mail:</label>
                    <label id="Mail-Inst"><?php echo isset($MailInst) ? htmlspecialchars($MailInst) : 'N/A'; ?></label>

                    <label style="text-align: right; font-weight: bold;">Cards On Hand:</label>
                    <label id="CardsOnHand"><?php echo isset($CardsOnHand) ? htmlspecialchars($CardsOnHand) : '0'; ?></label>

                    <label style="text-align: right; font-weight: bold;">Account Balance:</label>
                    <label id="AccountBalance" style="color: <?php echo isset($AccountBalance) && $AccountBalance > 0.85 ? 'green' : 'red'; ?>">
                        <?php echo isset($AccountBalance) ? htmlspecialchars($AccountBalance) : '0'; ?>
                    </label>

                    <input type="hidden" name="letter" id="hiddenLetter" value="<?php echo htmlspecialchars($selectedLetter ?? ''); ?>" />
                    <input type="hidden" name="ID" value="<?php echo htmlspecialchars($ID ?? ''); ?>" />
                    <button type="submit" id="submitCardButton" style="padding: 6px 12px; grid-column: 1 / span 2;" disabled>Submit to Add Card</button>
                </div>
            </form>
        </div>

        <div style="flex: 1; border: 1px solid; padding: 10px;">
         
               
    <h2>Stamp Summary</h2>
 
    
    <?php if (!empty($aggregatedStamps)): ?>
        <table>
            <thead>
                <tr>
                    <th>Value Of Stamps</th>
                    <th class="stamps-on-hand">Stamps On Hand</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalOnHand = 0;

                foreach ($aggregatedStamps as $row): 
                    $totalOnHand += $row['Stamps On Hand'];
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Value'] ?? '') ?></td>
                        <td class="stamps-on-hand"><?= (int)$row['Stamps On Hand'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
            </ul>
        </div>

    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
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

    
    cardsToMailInput.disabled = true;
    weightInput.disabled = true;
    postageCostInput.disabled = true;
    otherCostInput.disabled = true;
    totalCostInput.disabled = true;

    letterForm.addEventListener('submit', function(event) {
        if (!event.target.querySelector('input[name="letter_select_form"]')) {
            return;
        }

        event.preventDefault();

        const formData = new FormData(event.target);
        fetch(event.target.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            callInput.disabled = false;
            updateStatusLabel();
            updateMailInstLabel();
            updateAccountBalanceLabel();
            updateCardsOnHandLabel();
        });
    });


    callInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        cardsToMailInput.disabled = !hasValue;

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

    cardsToMailInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        weightInput.disabled = !hasValue;

        if (!hasValue) {
            weightInput.value = '';
            postageCostInput.value = '';
            totalCostInput.value = '';
            otherCostInput.disabled = true;
            postageCostInput.disabled = true;
            totalCostInput.disabled = true;
        }
    });

    weightInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        postageCostInput.disabled = !hasValue;

        if (!hasValue) {
            postageCostInput.value = '';
            totalCostInput.value = '';
            totalCostInput.disabled = true;
        }
    });

    postageCostInput.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        otherCostInput.disabled = !hasValue;

        if (!hasValue) {
            otherCostInput.value = '';
        }
        calculateTotal();
    });

    function calculateTotal() {
        const postageCost = parseFloat(postageCostInput.value) || 0;
        const otherCost = parseFloat(otherCostInput.value) || 0;
        totalCostInput.value = (postageCost + otherCost).toFixed(2);
    }

    postageCostInput.addEventListener('input', calculateTotal);
    otherCostInput.addEventListener('input', calculateTotal);

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

    function validateNumericInput(event) {
        const value = event.target.value;
        if (!/^\d*\.?\d*$/.test(value)) {
            event.target.value = value.replace(/[^\d.]/g, '');
        }
        if ((value.match(/\./g) || []).length > 1) {
            event.target.value = value.replace(/\.+$/, '');
        }
    }

    cardsToMailInput.addEventListener('input', validateNumericInput);
    weightInput.addEventListener('input', validateNumericInput);
    postageCostInput.addEventListener('input', validateNumericInput);

    function normalizeStatus(status) {
        return status.trim().replace(/\.$/, "");
    }

    function toggleSubmitButton() {
        const invalidStatuses = ["No status found", "Enter a call sign", "N/A", missingDataText];
        const status = normalizeStatus(statusLabel.textContent);
        submitCardButton.disabled = invalidStatuses.includes(status);
    }

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

    function updateMailInstLabel() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            mailInstLabel.textContent = 'Enter a call sign';
            return;
        }

        updateLabel(mailInstLabel, '../backend/fetch_mail_inst.php', { letter, call });
    }

    function updateAccountBalanceLabel() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            accountBalanceLabel.textContent = 'Enter a call sign';
            return;
        }

        updateLabel(accountBalanceLabel, '../backend/fetch_account_balance.php', { letter, call });
    }

    function updateCardsOnHandLabel() {
        const call = callInput.value.trim();
        const letter = letterSelect.value;

        if (!call) {
            cardsOnHandLabel.textContent = 'Enter a call sign';
            return;
        }

        updateLabel(cardsOnHandLabel, '../backend/fetch_cards_on_hand.php', { letter, call });
    }

    callInput.addEventListener('blur', function() {
        updateStatusLabel();
        updateMailInstLabel();
        updateAccountBalanceLabel();
        updateCardsOnHandLabel();
    });

    postageCostInput.addEventListener('click', function() {
        const letter = letterSelect.value;
        const weight = weightInput.value;
        if (weight) {
           // openPopupWindow(letter, weight);  // Make sure this function is defined if you're using it.
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

