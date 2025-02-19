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
$root  = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Delete Operator";
include("$root/backend/header.php");
include_once("$root/backend/functions.php");
// Ensure the user is logged in.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'Admin';
$user = strtoupper($_SESSION['username'] ?? 'No Call');
$available_roles = ['User', 'Admin', 'Ops'];

// --- Default values for the form fields ---
$callsign     = (string)($callsign ?? '');
$roleField    = (string)($roleField ?? 'User');

$message = ""; // Message to display to the user

// --- Process POST data ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $callsign = strtoupper(trim($_POST['callsign'] ?? ''));
    
    if (empty($callsign)) {
        $message = "Callsign is required.";
    } else {
        switch($_POST['action']) {
            case 'fetch_qrz':
                if ($_POST['status'] === 'Custom Address') {
                    $message = "Cannot fetch QRZ data when Custom Address is selected";
                } else {
                    require_once("$root/backend/qrz_fetch.php");
                    $result = fetchQRZData($callsign, $config);
                    
                    if (isset($result['error'])) {
                        $message = "QRZ Error: " . $result['error'];
                    } else {
                        // Populate form variables
                        foreach ($result as $key => $value) {
                            $$key = $value;
                        }
                        $message = "Data retrieved from QRZ for $callsign";
                    }
                }
                break;

            case 'clear':
                // Reset all variables to default
                $suffix = $first_name = $last_name = $class = "";
                $date_start = $date_exp = $new_call = $old_call = "";
                $address = $address2 = $city = $state = $zip = "";
                $email = $phone = $born = "";
                $status = "Active";
                $message = "Form cleared";
                break;

        

            case 'delete':
                // --- UPDATE OPERATOR DATA ---
                $callsign = strtoupper(trim($_POST['callsign'] ?? ''));
                if (empty($callsign)) {
                    $message = "Callsign is required for adding.";
                } else {
                    $selected_letter = getFirstLetterAfterNumber($callsign);
                    try {
                        $dbInfo = $config['db'];
                        $pdo = getPDOConnection($dbInfo);
                        if ($pdo) {
                            // Simple direct comparison first
                            if (strtoupper($user) === strtoupper($callsign)) {
                                $hasAccess = true;
                            } else {
                                // Check section permissions only if not self-editing
                                $checkSql = "SELECT 1 FROM `sections` 
                                           WHERE `call` = :call 
                                           AND `letter` = :letter 
                                           AND `status` = 'Edit'";
                                $checkStmt = $pdo->prepare($checkSql);
                                $checkStmt->bindValue(':call', $user, PDO::PARAM_STR);
                                $checkStmt->bindValue(':letter', $selected_letter, PDO::PARAM_STR);
                                $checkStmt->execute();
                                $hasAccess = $checkStmt->fetch(PDO::FETCH_COLUMN) ? true : false;
                            }

                            if (!$hasAccess) {
                                $message = "Access denied for user: $user for editing $callsign";
                            } else {
                                if (isset($config['sections'][$selected_letter]))
                                
                                {
                                    $dbInfo = $config['sections'][$selected_letter];
                                    $pdo = getPDOConnection($dbInfo);
                                    if ($pdo) {
                                        // Sanitize and collect all form fields
                                        
   
                                        // Build and execute the DELETE query
                                        $sql = "DELETE FROM tbl_Operator WHERE `Call` = :call";

                                        $stmt = $pdo->prepare($sql);
                                        $stmt->bindValue(':call', $callsign, PDO::PARAM_STR);
                                        if ($role === 'Admin') {
                                             $sectionStatus = 'Edit';
                                        }
                                        elseif ($role === 'Ops') {
                                            $sectionStatus = 'View';
                                        }
                                        else {
                                            $sectionStatus = 'View';
                                        }
                                        upsertUserAndSection($callsign, "", "", "", "", true);
                                        
                                        if ($stmt->execute()) {
                                            $message = "Record updated successfully for callsign: $callsign";
                                        } else {
                                            $message = "Error updating record.";
                                        }
                                    }
                                } else {
                                    $message = "Invalid callsign for your access.";
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}
?>
<div class="center-content">
    <?php if ($role == 'User'): ?>
    <img src="/7thArea.png" alt="7th Area" />
    <?php endif; ?>
</div>
<div id="operator-add-container">
    <h1 class="center-content">Add Operator</h1>
    <div id="messageDiv" class="center-content">
        <?php if (!empty($message)) { echo htmlspecialchars($message ?? ''); } ?></div>
    <div class="form-wrapper">
        <form method="post" action="operator-del.php" id="dataForm">
            <!-- Callsign Field (remains required) -->
            <div style="display: flex; align-items: center;">
                <label for="callsign" style="margin-right: 10px; white-space: nowrap;">Callsign:</label>
                <input type="text" id="callsign" name="callsign"
                    value="<?php echo htmlspecialchars($callsign ?? ''); ?>" required>
            </div>

            <!-- Form Buttons -->
            <div style="display: flex; justify-content: center; align-items: center; width: 100%; margin: 20px auto;">
                <div style="display: flex; gap: 50px; justify-content: center; align-items: center; min-width: 800px;">
                    <button type="submit" name="action" value="delete"
                        style="flex: 0 0 200px; padding: 12px 24px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Delete Operator
                    </button>

                    <button type="submit" name="action" value="clear"
                        style="flex: 0 0 200px; padding: 12px 24px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Clear Form
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php


$footerPath = "$root/backend/footer.php";
include($footerPath);
?>