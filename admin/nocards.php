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

$title = "Calls DNU Silent Key No Cards";

$selectedSection   = null;
// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
include("$root/backend/header.php");


// ----------------- MAIN -----------------
$dataRows = [];
$row = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSection = $_POST['section'] ?? null;

    if ($selectedSection && isset($config['sections'][$selectedSection])) {
        $dbInfo = $config['sections'][$selectedSection];
        $pdo = getPDOConnection($dbInfo);

        // Fetch data from the specified table
        $dataRows = fetchDataNew($pdo, 'tbl_Operator');
    } else {
        echo "Error: Invalid or missing section configuration.";
    }
}

?>

<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau - No Cards</h1>

    <form method="POST">
        <label for="section">Select a Section:</label>
        <select name="section" id="section">
            <?php
            if (isset($config['sections']) && is_array($config['sections'])):
                foreach ($config['sections'] as $section => $dbCreds):
            ?>
                    <option value="<?= htmlspecialchars($section ?? '') ?>" 
                            <?= ($selectedSection === $section) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($section ?? '') ?>
                    </option>
            <?php
                endforeach;
            else:
            ?>
                <option value="">No sections available</option>
            <?php endif; ?>
        </select>
        <button type="submit">Submit</button>
    </form>
    
    <?php if (!empty($dataRows)): ?>
        <table>
            <thead>
                <tr>
                <th>Call</th>
                   <!-- <th>Cards Received</th>
                    <th>Cards Returned</th>
                    <th>Money Received</th>
                    <th>Paid</th> -->
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Lic-exp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dataRows as $row): ?>
                    <?php if ($row['Status'] !== 'Active'): ?>
                    <tr>
                    
                        <td><?= htmlspecialchars($row['Call']) ?></td>
                       <!-- <td><?= htmlspecialchars($row['CardsReceived']) ?></td>
                        <td><?= htmlspecialchars($row['CardsReturned']) ?></td>
                        <td><?= htmlspecialchars($row['MoneyReceived']) ?></td> 
                        <td><?= htmlspecialchars($row['Paid']) ?></td> -->
                        <td><?= htmlspecialchars($row['Status'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['Remarks'] ?? '') ?></td>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($row['Lic-exp'] ?? ''))) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        
    <?php endif; ?>
</div>

<?php
include("$root/backend/footer.php");
?>
