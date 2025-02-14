<?php
session_start();
$root  = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Edit Operator";
include("$root/backend/header.php");

// Ensure the user is logged in.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'Admin';
$user = strtoupper($_SESSION['username'] ?? 'No Call');
$available_roles = ['User', 'Admin', 'Ops'];

// --- Default values for the form fields ---
$callsign     = "";
$suffix       = "";
$first_name   = "";
$last_name    = "";
$class        = "";
$date_start   = "";
$date_exp     = "";
$new_call     = "";
$old_call     = "";
$address      = "";
$address2     = "";
$city         = "";
$state        = "";
$zip          = "";
$country      = "";
$email        = "";
$phone        = "";
$born         = "";
$customAddress= "";
$roleField    = "User";

$message = ""; // Message to display to the user

// --- Process POST data ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && $_POST['action'] === "load") {
        $callsign = strtoupper(trim($_POST['callsign'] ?? ''));
        if (empty($callsign)) {
            $message = "Callsign is required.";
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
                                $sql = "SELECT * FROM tbl_Operator WHERE `Call` = :call LIMIT 1";
                                $stmt = $pdo->prepare($sql);
                                $stmt->bindValue(':call', $callsign, PDO::PARAM_STR);
                                
                                if ($stmt->execute()) {
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if ($result) {
                                        // Pre-fill form variables from the database record
                                        $callsign      = $result['Call']       ?? "";
                                        $suffix        = $result['Suffix']     ?? "";
                                        $first_name    = $result['FirstName']  ?? "";
                                        $last_name     = $result['LastName']   ?? "";
                                        $class         = $result['Class']      ?? "";
                                        $date_start    = $result['Lic-issued'] ?? "";
                                        $date_exp      = $result['Lic-exp']    ?? "";
                                        $new_call      = $result['NewCall']    ?? "";
                                        $old_call      = $result['Old_call']   ?? "";
                                        $address       = $result['Address_1']  ?? "";
                                        $address2      = $result['Address_2']  ?? "";
                                        $city          = $result['City']       ?? "";
                                        $state         = $result['State']      ?? "";
                                        $zip           = $result['Zip']        ?? "";
                                        $country       = $result['Country']    ?? "";
                                        $email         = $result['E-Mail']     ?? "";
                                        $phone         = $result['Phone']      ?? "";
                                        $born          = $result['DOB']        ?? "";
                                        $customAddress = $result['Status']     ?? "";
                                        $roleField     = $result['Role']       ?? "User";
                                        $message = "Data loaded for callsign: $callsign";
                                    } else {
                                        $message = "No record found for callsign: $callsign";
                                    }
                                } else {
                                    $message = "Error executing query";
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
    }
elseif (isset($_POST['action']) && $_POST['action'] === "update") {
    // --- UPDATE OPERATOR DATA ---
    $callsign = strtoupper(trim($_POST['callsign'] ?? ''));
    if (empty($callsign)) {
        $message = "Callsign is required for update.";
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
                            } else {
                            $customAddress = trim($_POST['customAddress'] ?? '');
                            if ($role === 'Admin') {
                                $roleField = trim($_POST['role'] ?? 'User');
                            }
                        }
                            $updated = date("Y-m-d");

                            // Build and execute the UPDATE query
                            $sql = "UPDATE tbl_Operator SET
                                    `Suffix` = :suffix,
                                    `FirstName` = :first_name,
                                    `LastName` = :last_name,
                                    `Class` = :class,
                                    `Lic-issued` = :lic_issued,
                                    `Lic-exp` = :lic_exp,
                                    `NewCall` = :new_call,
                                    `Old_call` = :old_call,
                                    `Address_1` = :address1,
                                    `Address_2` = :address2,
                                    `City` = :city,
                                    `State` = :state,
                                    `Zip` = :zip,
                                    `E-Mail` = :email,
                                    `Phone` = :phone,
                                    `DOB` = :dob,
                                    `Status` = :status,
                                    `Updated` = :updated
                                WHERE `Call` = :call";

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
                            $stmt->bindValue(':status', $customAddress, PDO::PARAM_STR);
                            $stmt->bindValue(':updated', $updated, PDO::PARAM_STR);
                            $stmt->bindValue(':call', $callsign, PDO::PARAM_STR);

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
}
}
?>
<div class="center-content">
    <?php if ($role == 'User'): ?>
        <img src="/7thArea.png" alt="7th Area" />
    <?php endif; ?>
</div>
<div id="operator-add-container">
    <h1 class="center-content">Edit Operator</h1>
    <div id="messageDiv" class="center-content"><?php if (!empty($message)) { echo htmlspecialchars($message); } ?></div>
    <div class="form-wrapper">
        <form method="post" action="operator-edit.php" id="dataForm">
            <!-- Callsign Field (remains required) -->
            <div style="display: flex; align-items: center;">
                <label for="callsign" style="margin-right: 10px; white-space: nowrap;">Callsign:</label>
                <input type="text" id="callsign" name="callsign" value="<?php echo htmlspecialchars($callsign); ?>" required>
            </div>

            <!-- Suffix Field -->
            <div style="display: flex; align-items: center;">
                <label for="suffix" style="margin-right: 10px; white-space: nowrap;">Suffix:</label>
                <input type="text" id="suffix" name="suffix" value="<?php echo htmlspecialchars($suffix); ?>">
            </div>

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
                <input type="text" id="born" name="born" value="<?php echo htmlspecialchars($born); ?>">
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

            <!-- Status / Custom Address Field -->
            <?php if ($role == 'Admin'): ?>
                <div style="display: flex; align-items: center;">
                    <label for="customAddress" style="margin-right: 10px; white-space: nowrap;">Status</label>
                    <select name="customAddress" id="customAddress">
                        <option value="Active" <?php if ($customAddress === 'Active') echo 'selected'; ?>>Active</option>
                        <option value="Custom Address" <?php if ($customAddress === 'Custom Address') echo 'selected'; ?>>Custom Address</option>
                        <option value="New" <?php if ($customAddress === 'New') echo 'selected'; ?>>New</option>
                        <option value="Via" <?php if ($customAddress === 'Via') echo 'selected'; ?>>Via</option>
                    </select>
                </div>
            <?php else: ?>
                <div style="display: flex; align-items: center;">
                    <label for="customAddress" style="margin-right: 10px; white-space: nowrap;">Custom Address</label>
                    <input type="checkbox" id="customAddress" name="customAddress" <?php if ($customAddress === 'On') echo 'checked'; ?>>
                </div>
            <?php endif; ?>

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
            <div class="full-width" style="display: flex; align-items: center; justify-content: center;">
                <button type="submit" name="action" value="load" formnovalidate>Load Operator Data</button>
            </div>
            <br>
            <div class="full-width" style="display: flex; align-items: center; justify-content: center;">
                <button type="submit" name="action" value="update" id="submitButton">Update Operator</button>
            </div>
            <br>
            <div class="full-width" style="display: flex; align-items: center; justify-content: center;">
                <button type="button" id="fetchButton">Fetch Data from QRZ</button>
            </div>
            <br>
            <div class="full-width" style="display: flex; align-items: center; justify-content: center;">
                <button type="reset">Clear</button>
            </div>
            </div>
        </form>
    </div>
</div>

<?php

$java = "$root/backend/java3.php";
include($java);

$footerPath = "$root/backend/footer.php";
include($footerPath);
?>
