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
$title = "Edit Operator";
include("$root/backend/header.php");
include_once("$root/backend/functions.php");
// Ensure the user is logged in.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

#echo "<pre>" . print_r($_POST, true) . "</pre>";

$role = $_SESSION['role'] ?? 'Admin';
$user = strtoupper($_SESSION['username'] ?? 'No Call');
$available_roles = ['User', 'Admin', 'Ops'];

// --- Default values for the form fields ---
$callsign     = (string)($callsign ?? '');
$suffix       = (string)($suffix ?? '');
$first_name   = (string)($first_name ?? '');
$last_name    = (string)($last_name ?? '');
$class        = (string)($class ?? '');
$date_start   = (string)($date_start ?? '');
$date_exp     = (string)($date_exp ?? '');
$new_call     = (string)($new_call ?? '');
$old_call     = (string)($old_call ?? '');
$address      = (string)($address ?? '');
$address2     = (string)($address2 ?? '');
$city         = (string)($city ?? '');
$state        = (string)($state ?? '');
$zip          = (string)($zip ?? '');
$country      = (string)($country ?? '');
$email        = (string)($email ?? '');
$phone        = (string)($phone ?? '');
$born         = (string)($born ?? '');
$status= (string)($status ?? '');
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

        

            case 'update':
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
                                if (isset($config['sections'][$selected_letter])) {
                                    $dbInfo = $config['sections'][$selected_letter];
                                    $pdo = getPDOConnection($dbInfo);
                                    if ($pdo) {
                                        // Sanitize and collect all form fields
                                        $suffix        = trim($_POST['suffix'] ?? '');
                                        $first_name    = trim($_POST['first_name'] ?? '');
                                        $last_name     = trim($_POST['last_name'] ?? '');
                                        $class         = trim($_POST['class'] ?? '');
                                        $date_start    = trim($_POST['date_start'] ?? '');
                                        $date_exp      = trim($_POST['date_exp'] ?? '');
                                        $new_call      = trim($_POST['new_call'] ?? '');
                                        $old_call      = trim($_POST['old_call'] ?? '');
                                        $address       = trim($_POST['address'] ?? '');
                                        $address2      = trim($_POST['address2'] ?? '');
                                        $city          = trim($_POST['city'] ?? '');
                                        $state         = trim($_POST['state'] ?? '');
                                        $zip           = trim($_POST['zip'] ?? '');
                                        $email         = trim($_POST['email'] ?? '');
                                        $phone         = trim($_POST['phone'] ?? '');
                                        $dob          = trim($_POST['born'] ?? '');
                                        $status = trim($_POST['status'] ?? 'Active');  // Use consistent field name
                                           // Convert date formats for the 'born' field if needed
                                        if ($dob !== null) {
                                            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dob)) { // YYYY-MM-DD HH:MM:SS format
                                                $born = $dob;
                                            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) { // YYYY-MM-DD format
                                                $born = $dob . ' 00:00:00';
                                            } elseif (preg_match('/^\d{4}$/', $dob)) { // YYYY format (from QRZ)
                                                $born = $dob . '-01-01 00:00:00';
                                            } else {
                                                $born = null; // Invalid format
                                            }
                                        } 
                                        $status = trim($_POST['status'] ?? '');
                                        if ($role === 'Admin') {
                                            $roleField = trim($_POST['role'] ?? 'User');
                                        }
                                    
                                        $updated = date("Y-m-d");

                                        // Build and execute the UPDATE query
                                        $sql = "INSERT INTO tbl_Operator (
                                            `Call`, `Suffix`, `FirstName`, `LastName`, `Class`, 
                                            `Lic-issued`, `Lic-exp`, `NewCall`, `Old_call`,
                                            `Address_1`, `Address_2`, `City`, `State`, `Zip`,
                                            `E-Mail`, `Phone`, `DOB`, `Status`, `Updated`
                                        ) VALUES (
                                            :call, :suffix, :first_name, :last_name, :class,
                                            :lic_issued, :lic_exp, :new_call, :old_call,
                                            :address1, :address2, :city, :state, :zip,
                                            :email, :phone, :dob, :status, :updated
                                        )";

                                        $stmt = $pdo->prepare($sql);
                                        $stmt->bindValue(':suffix', $suffix, PDO::PARAM_STR);
                                        $stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
                                        $stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
                                        $stmt->bindValue(':class', $class, PDO::PARAM_STR);
                                        $stmt->bindValue(':lic_issued', $date_start, PDO::PARAM_STR);
                                        $stmt->bindValue(':lic_exp', $date_exp, PDO::PARAM_STR);
                                        $stmt->bindValue(':new_call', $new_call, PDO::PARAM_STR);
                                        $stmt->bindValue(':old_call', $old_call, PDO::PARAM_STR);
                                        $stmt->bindValue(':address1', $address, PDO::PARAM_STR);
                                        $stmt->bindValue(':address2', $address2, PDO::PARAM_STR);
                                        $stmt->bindValue(':city', $city, PDO::PARAM_STR);
                                        $stmt->bindValue(':state', $state, PDO::PARAM_STR);
                                        $stmt->bindValue(':zip', $zip, PDO::PARAM_STR);
                                        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                                        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
                                        $stmt->bindValue(':dob', $born, PDO::PARAM_STR);
                                        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
                                        $stmt->bindValue(':updated', $updated, PDO::PARAM_STR);
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
                                        upsertUserAndSection($callsign, $roleField, $email, $selected_letter, $sectionStatus) ;
                                        
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
    <div id="messageDiv" class="center-content"><?php if (!empty($message)) { echo htmlspecialchars($message ?? ''); } ?></div>
    <div class="form-wrapper">
        <form method="post" action="operator-add.php" id="dataForm">
            <!-- Callsign Field (remains required) -->
            <div style="display: flex; align-items: center;">
                <label for="callsign" style="margin-right: 10px; white-space: nowrap;">Callsign:</label>
                <input type="text" id="callsign" name="callsign" value="<?php echo htmlspecialchars($callsign ?? ''); ?>" required>
            </div>

           <!-- Status / Custom Address Field -->
           <?php if ($role == 'Admin'): ?>
                <div style="display: flex; align-items: center;">
                    <label for="status" style="margin-right: 10px; white-space: nowrap;">Status</label>
                    <select name="status" id="status">
                        <option value="Active" <?php echo ($status === 'Active' ? 'selected' : ''); ?>>Active</option>
                        <option value="Custom Address" <?php echo ($status === 'Active_DIFF_Address' ? 'selected' : ''); ?>>Custom Address</option>
                        <option value="Active_DIFF_Addre" <?php echo ($status === 'DNU-DESTROY' ? 'selected' : ''); ?>>DNU-DESTROY</option>
                        <option value="License Expired" <?php echo ($status === 'License Expired' ? 'selected' : ''); ?>>License Expired</option>
                        <option value="New" <?php echo ($status === 'New' ? 'selected' : ''); ?>>New</option>
                        <option value="SILENT KEY" <?php echo ($status === 'SILENT KEY' ? 'selected' : ''); ?>>SILENT KEY</option>
                        <option value="Via" <?php echo ($status === 'Via' ? 'selected' : ''); ?>>Via</option>
                    </select>
                </div>
            <?php else: ?>
                <div style="display: flex; align-items: center;">
                    <label for="status" style="margin-right: 10px; white-space: nowrap;">Custom Address</label>
                    <input type="checkbox" id="status" name="status" <?php if ($status === 'On') echo 'checked'; ?>>
                </div>
            <?php endif; ?>

            <!-- First Name Field (required removed) -->
            <div style="display: flex; align-items: center;">
                <label for="first_name" style="margin-right: 10px; white-space: nowrap;">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
            </div>

            <!-- Last Name Field (required removed) -->
            <div style="display: flex; align-items: center;">
                <label for="last_name" style="margin-right: 10px; white-space: nowrap;">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
            </div>

            <!-- Class Field -->
            <div style="display: flex; align-items: center;">
                <label for="class" style="margin-right: 10px; white-space: nowrap;">Class:</label>
                <input type="text" id="class" name="class" value="<?php echo htmlspecialchars($class); ?>">
            </div>

            <!-- Start Date Field -->
            <div style="display: flex; align-items: center;">
                <label for="date_start" style="margin-right: 10px; white-space: nowrap;">Start Date:</label>
                <input type="text" id="date_start" name="date_start" value="<?php echo htmlspecialchars($date_start); ?>">
            </div>

            <!-- Expiration Date Field -->
            <div style="display: flex; align-items: center;">
                <label for="date_exp" style="margin-right: 10px; white-space: nowrap;">Expiration Date:</label>
                <input type="text" id="date_exp" name="date_exp" value="<?php echo htmlspecialchars($date_exp); ?>">
            </div>

            <!-- New Call Field -->
            <div style="display: flex; align-items: center;">
                <label for="new_call" style="margin-right: 10px; white-space: nowrap;">New Call:</label>
                <input type="text" id="new_call" name="new_call" value="<?php echo htmlspecialchars($new_call); ?>">
            </div>

            <!-- Old Call Field -->
            <div style="display: flex; align-items: center;">
                <label for="old_call" style="margin-right: 10px; white-space: nowrap;">Old Call:</label>
                <input type="text" id="old_call" name="old_call" value="<?php echo htmlspecialchars($old_call); ?>">
            </div>

            <!-- Born Field -->
            <div style="display: flex; align-items: center;">
                <label for="born" style="margin-right: 10px; white-space: nowrap;">Born:</label>
                <input type="text" id="born" name="born" value="<?php echo htmlspecialchars($born ?? ''); ?>"> 
            </div>

            <!-- Address Fields -->
            <div style="display: flex; align-items: center;" class="full-width">
                <label for="address" style="margin-right: 10px; white-space: nowrap;">Address:</label>
                <input type="text" id="address" name="address" class="full-width-input" value="<?php echo htmlspecialchars($address); ?>">
            </div>
            <div style="display: flex; align-items: center;" class="full-width">
                <label for="address2" style="margin-right: 10px; white-space: nowrap;">Address 2:</label>
                <input type="text" id="address2" name="address2" class="full-width-input" value="<?php echo htmlspecialchars($address2); ?>">
            </div>

            <!-- City, State, Zip, and Country Fields -->
            <div style="display: flex; align-items: center;">
                <label for="city" style="margin-right: 10px; white-space: nowrap;">City:</label>
                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>">
            </div>
            <div style="display: flex; align-items: center;">
                <label for="state" style="margin-right: 10px; white-space: nowrap;">State:</label>
                <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($state); ?>">
            </div>
            <div style="display: flex; align-items: center;">
                <label for="zip" style="margin-right: 10px; white-space: nowrap;">Zip:</label>
                <input type="text" id="zip" name="zip" value="<?php echo htmlspecialchars($zip); ?>">
            </div>

            <!-- Email and Phone Fields -->
            <div style="display: flex; align-items: center;">
                <label for="email" style="margin-right: 10px; white-space: nowrap;">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div style="display: flex; align-items: center;">
                <label for="phone" style="margin-right: 10px; white-space: nowrap;">Phone:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
            </div>



            <!-- Role Selection for Admin Users -->
            <?php if ($role == 'Admin'): ?>
                <div style="display: flex; align-items: center;">
                    <label for="role" style="margin-right: 10px; white-space: nowrap;">Role:</label>
                    <select id="role" name="role">
                        <?php foreach ($available_roles as $available_role): ?>
                            <option value="<?php echo $available_role; ?>" <?php if ($available_role === $roleField) echo 'selected'; ?>>
                                <?php echo $available_role; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Form Buttons -->
<div style="display: flex; justify-content: center; align-items: center; width: 100%; margin: 20px auto;">
    <div style="display: flex; gap: 50px; justify-content: center; align-items: center; min-width: 800px;">
        <button type="submit" 
                name="action" 
                value="update" 
                
                style="flex: 0 0 200px; padding: 12px 24px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Add Operator
        </button>
        
        <button type="submit" 
                name="action" 
                value="fetch_qrz"
                
                style="flex: 0 0 200px; padding: 12px 24px; background-color: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Fetch Data from QRZ
        </button>
        
        <button type="submit" 
                name="action" 
                value="clear"
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

