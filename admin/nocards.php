<?php
/*
Copyright © 2024 NA7KR Kevin Roberts. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
You may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0
*/


session_start();
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Users to Pay Page";

include("$root/backend/header.php");

// Check login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Include dependencies
require_once "$root/backend/functions.php";

// Get email config
$emailConfig = $config['email'] ?? [];

// Initialize variables
$selectedLetter = $_POST['letter'] ?? null;
$filterEmail = isset($_POST['filter_email']);
$filterNoEmail = isset($_POST['filter_no_email']);
$printSelected = isset($_POST['print_selected']);
$operatorData = $redData = [];

// Function to safely process data
function processCardData($rawCardData) {
    $total = 0;
    if (!is_array($rawCardData)) {
        return 0; // Prevent errors if data is not an array
    }
    foreach ($rawCardData as $row) {
        if (is_array($row) && isset($row['CardsReceived'])) {
            $total += (int)$row['CardsReceived'];
        }
    }
    return $total;
}

// Fetch data if section is selected
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedLetter && isset($config['sections'][$selectedLetter])) {
    $pdo = getPDOConnection($config['sections'][$selectedLetter]);

    if ($pdo) {
        // Fetch relevant data
        $cardData = fetchData($pdo, 'tbl_CardRec', '*', null, null, false, true);
        $mailedData = fetchData($pdo, 'tbl_CardM', '*', null, null, false, true);
        $returnedData = fetchData($pdo, 'tbl_CardRet', '*', null, null, false, true);
        $operatorData = fetchData($pdo, 'tbl_Operator', '*', null, null, false, true);

        // Ensure data is properly formatted
        $cardData = is_array($cardData) ? $cardData : [];
        $mailedData = is_array($mailedData) ? $mailedData : [];
        $returnedData = is_array($returnedData) ? $returnedData : [];
        $operatorData = is_array($operatorData) ? $operatorData : [];

        // Process Data
        $totalCardsReceived = processCardData($cardData);
        $totalCardsMailed = processCardData($mailedData);
        $totalCardsReturned = processCardData($returnedData);

        // Fetch users who owe money
        $netBalanceThreshold = 100;
        $statusList = ['License Expired', 'SILENT KEY', 'DNU-DESTROY', 'Inactive'];
        $redData = fetchFilteredData($pdo, $netBalanceThreshold, $statusList,null);

        // Apply filters
        if ($filterEmail) {
            $redData = array_filter($redData, fn($entry) => !empty($entry['Email']));
        }
        if ($filterNoEmail) {
            $redData = array_filter($redData, fn($entry) => empty($entry['Email']));
        }

        // Handle email sending
        if (isset($_POST['email_selected']) && !empty($_POST['selected_calls'])) {
            foreach ($_POST['selected_calls'] as $selectedCall) {
                foreach ($redData as $data) {
                    if ($data['Call'] === $selectedCall && !empty($data['Email'])) {
                        sendEmail($data['Email'], $data['Call'], $data['CardsOnHand'], $emailConfig);
                    }
                }
            }
        }

       
    } else {
        echo "Error: Unable to establish database connection.";
    }
}
?>
    <style>
        .center-content form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .center-content form label {
            margin-bottom: 10px;
        }
        .center-content form input,
        .center-content form select,
        .center-content form button {
            margin-bottom: 20px;
        }
    </style>
<!-- HTML Form -->
<div class="center-content">
    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>

    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php foreach ($config['sections'] as $letter => $dbInfo): ?>
                <option value="<?= htmlspecialchars($letter) ?>" <?= ($selectedLetter === $letter) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($letter) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($redData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>

        <form method="POST">
            <input type="hidden" name="letter" value="<?= htmlspecialchars($selectedLetter) ?>">

            <table border="1">
                <thead>
                    <tr> 
                        <th>Call</th>
                        <th>Remarks</th>
                        <th>Lic-exp</th>
                        <th>Cards On Hand</th>
                        <th>Net Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($redData as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Call'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['Remarks'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(!empty($row['Lic-exp']) ? date('Y-m-d', strtotime($row['Lic-exp'])) : '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['CardsOnHand'] ?? '0', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['NetBalance'] ?? '0', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['Status'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </form>
    <?php endif; ?>
</div>

<?php include("$root/backend/footer.php"); ?>
