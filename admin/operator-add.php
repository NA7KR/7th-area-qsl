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
#print_r($_POST);
$callExists = false;
$msgecho = "";
$root  = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Page to Add Operator ";
//$config = include('../config.php');
include("$root/backend/header.php");

$selectedLetter = null;

// Ensure the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'] ?? 'Admin';

// Retrieve the selected letter from session, if available
$selectedLetter = $_SESSION['selected_letter'] ?? '';

// Handle the letter selection form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['letter_select_form']) && $_POST['letter_select_form'] == 1) {
    if (isset($_POST['letter'])) {
        $_SESSION['selected_letter'] = $_POST['letter'];
        // Redirect to prevent form resubmission on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$isLoggedIn      = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$role            = $_SESSION['role'] ?? 'Admin';
$user = strtoupper($_SESSION['username'] ?? 'No Call');
$available_roles = ['User', 'Admin', 'Ops'];

// Enable error reporting for debugging
ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
?>
<div class="center-content"></div>
<div id="operator-add-container">
    <h1 class="center-content">Add New User</h1>

    <div id="messageDiv"></div>
    <div class="form-wrapper">
        <form method="post" id="dataForm">
            <!-- Callsign Field -->
            <div style="display: flex; align-items: center;">
                <label for="callsign" style="margin-right: 10px; white-space: nowrap;">Callsign:</label>
                <input type="text" id="callsign" name="callsign" required>
            </div>

            <!-- Suffix Field -->
            <div style="display: flex; align-items: center;">
                <label for="suffix" style="margin-right: 10px; white-space: nowrap;">Suffix:</label>
                <input type="text" id="suffix" name="suffix">
            </div>

            <!-- First Name Field -->
            <div style="display: flex; align-items: center;">
                <label for="first_name" style="margin-right: 10px; white-space: nowrap;">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <!-- Last Name Field -->
            <div style="display: flex; align-items: center;">
                <label for="last_name" style="margin-right: 10px; white-space: nowrap;">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <!-- Class Field -->
            <div style="display: flex; align-items: center;">
                <label for="class" style="margin-right: 10px; white-space: nowrap;">Class:</label>
                <input type="text" id="class" name="class">
            </div>

            <!-- Start Date Field -->
            <div style="display: flex; align-items: center;">
                <label for="date_start" style="margin-right: 10px; white-space: nowrap;">Start Date:</label>
                <input type="text" id="date_start" name="date_start">
            </div>

            <!-- Expiration Date Field -->
            <div style="display: flex; align-items: center;">
                <label for="date_exp" style="margin-right: 10px; white-space: nowrap;">Expiration Date:</label>
                <input type="text" id="date_exp" name="date_exp">
            </div>

            <!-- New Call Field -->
            <div style="display: flex; align-items: center;">
                <label for="new_call" style="margin-right: 10px; white-space: nowrap;">New Call:</label>
                <input type="text" id="new_call" name="new_call">
            </div>

            <!-- Old Call Field -->
            <div style="display: flex; align-items: center;">
                <label for="old_call" style="margin-right: 10px; white-space: nowrap;">Old Call:</label>
                <input type="text" id="old_call" name="old_call">
            </div>

            <!-- Born Field -->
            <div style="display: flex; align-items: center;">
                <label for="born" style="margin-right: 10px; white-space: nowrap;">Born:</label>
                <input type="text" id="born" name="born">
            </div>

            <!-- Address Fields -->
            <div style="display: flex; align-items: center;" class="full-width">
                <label for="address" style="margin-right: 10px; white-space: nowrap;">Address:</label>
                <input type="text" id="address" name="address" class="full-width-input">
            </div>

            <div style="display: flex; align-items: center;" class="full-width">
                <label for="address2" style="margin-right: 10px; white-space: nowrap;">Address 2:</label>
                <input type="text" id="address2" name="address2" class="full-width-input">
            </div>

            <!-- City, State, Zip, and Country Fields -->
            <div style="display: flex; align-items: center;">
                <label for="city" style="margin-right: 10px; white-space: nowrap;">City:</label>
                <input type="text" id="city" name="city">
            </div>

            <div style="display: flex; align-items: center;">
                <label for="state" style="margin-right: 10px; white-space: nowrap;">State:</label>
                <input type="text" id="state" name="state">
            </div>

            <div style="display: flex; align-items: center;">
                <label for="zip" style="margin-right: 10px; white-space: nowrap;">Zip:</label>
                <input type="text" id="zip" name="zip">
            </div>
            <!--
            <div style="display: flex; align-items: center;">
                <label for="country" style="margin-right: 10px; white-space: nowrap;">Country:</label>
                <input type="text" id="country" name="country">
            </div> -->
            
            <!-- Email and Phone Fields -->
            <div style="display: flex; align-items: center;">
                <label for="email" style="margin-right: 10px; white-space: nowrap;">Email:</label>
                <input type="email" id="email" name="email">
            </div>

            <div style="display: flex; align-items: center;">
                <label for="phone" style="margin-right: 10px; white-space: nowrap;">Phone:</label>
                <input type="tel" id="phone" name="phone">
            </div>

            <!-- Custom Address / Status Field -->
            <?php if ($role == 'Admin'): ?>
                <div style="display: flex; align-items: center;">
                    <label for="customAddress" style="margin-right: 10px; white-space: nowrap;">Status</label>
                    <select name="customAddress" id="customAddress">
                        <option value="Active">Active</option>
                        <option value="Custom Address">Custom Address</option>
                        <option value="New">New</option>
                        <option value="Via">Via</option>
                    </select>
                </div>
            <?php else: ?>
                <div style="display: flex; align-items: center;">
                    <label for="customAddress" style="margin-right: 10px; white-space: nowrap;">Custom Address</label>
                    <input type="checkbox" id="customAddress" name="customAddress">
                </div>
            <?php endif; ?>

            <!-- Role Selection for Admin Users -->
            <?php if ($role == 'Admin'): ?>
                <div style="display: flex; align-items: center;">
                    <label for="role" style="margin-right: 10px; white-space: nowrap;">Role:</label>
                    <select id="role" name="role">
                        <?php foreach ($available_roles as $available_role): ?>
                            <option value="<?php echo $available_role; ?>" <?php if ($available_role === 'User') echo 'selected'; ?>>
                                <?php echo $available_role; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Hidden Field to Retain Selected Letter -->
            <input type="hidden" name="selected_letter" value="<?php echo htmlspecialchars($selectedLetter); ?>">
            <br>

            <!-- Form Buttons -->
            <div class="full-width" style="display: flex; align-items: center; justify-content: center;">
                <input type="submit" value="Submit" id="submitButton" disabled>
            </div>
            <br>

            <div class="full-width" style="display: flex; align-items: center; justify-content: center;">
                <button type="button" id="fetchButton">Fetch Data from QRZ</button>
            </div>
            <br>

            <div class="full-width" style="display: flex; align-items: center; justify-content: center;">
                <button type="button" id="clearButton">Clear</button>
            </div>
        </form>
    </div>

    <?php
    // Process form submission for adding a new operator (ignore letter select form)
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['letter_select_form'])) {
        // Sanitize incoming POST values
        $callsign    = htmlspecialchars($_POST['callsign']);
        $first_name  = htmlspecialchars($_POST['first_name']);
        $last_name   = htmlspecialchars($_POST['last_name']);
        $class       = htmlspecialchars($_POST['class']);
        $lic_issued  = htmlspecialchars($_POST['date_start']);
        $lic_exp     = htmlspecialchars($_POST['date_exp']);
        $new_call    = htmlspecialchars($_POST['new_call']);
        $old_call    = htmlspecialchars($_POST['old_call']);
        $address1    = htmlspecialchars($_POST['address']);
        $address2    = htmlspecialchars($_POST['address2']);
        $city        = htmlspecialchars($_POST['city']);
        $state       = htmlspecialchars($_POST['state']);
        $zip         = htmlspecialchars($_POST['zip']);
        //$country     = htmlspecialchars($_POST['country']);
        $email       = htmlspecialchars($_POST['email']);
        $phone       = htmlspecialchars($_POST['phone']);
        $dob         = htmlspecialchars($_POST['born'] ?? null);

        // Convert date formats for the 'born' field if needed
        if ($dob !== null) {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dob)) { // YYYY-MM-DD HH:MM:SS format
                $datetime = $dob;
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) { // YYYY-MM-DD format
                $datetime = $dob . ' 00:00:00';
            } elseif (preg_match('/^\d{4}$/', $dob)) { // YYYY format (from QRZ)
                $datetime = $dob . '-01-01 00:00:00';
            } else {
                $datetime = null; // Invalid format
            }
        }
        $dob = $datetime;
        $custom = htmlspecialchars($_POST['customAddress'] ?? 'Off');
        $role   = htmlspecialchars($_POST['role'] ?? 'User');

        // Additional fields set to null by default
        $pc_em_date    = $alt_phone = $mail_inst = $remarks = $attachments = $mail_label = $pc_sent = $status = $year_of_birth = null;
        $suffix        = htmlspecialchars($_POST['suffix'] ?? null);
        $updated       = date("Y-m-d");
     
        $selected_letter = getFirstLetterAfterNumber($callsign);
        //echo "Selected Letter: $selected_letter<br>";
        try{
            $dbInfo = $config['db'];
            $pdo = getPDOConnection($dbInfo);

            if ($pdo) {
                $checkSql = "SELECT CASE WHEN EXISTS (
                                 SELECT 1 FROM `sections` 
                                 WHERE `call` = :call 
                                   AND `letter` = :letter 
                                   AND `status` = 'Edit'
                               ) THEN 1 ELSE 0 END AS result;";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->bindValue(':call', $user, PDO::PARAM_STR);
                $checkStmt->bindValue(':letter', $selected_letter, PDO::PARAM_STR);
                $checkStmt->execute();
                
                // Fetch one row as an associative array.
                $access = $checkStmt->fetch(PDO::FETCH_ASSOC);  
            }
            
            if ($access['result'] == 0) {   
                $msgecho = "Access denied for user: $user and Role: $role adding  $callsign";
                exit;
            }             
        }
        catch (Exception $e) {
            $msgecho = "Error: " . $e->getMessage();
        }
        // 
        // If the selected letter returns an error, exit immediately
        if (str_starts_with($selected_letter, "Error:")) {
            exit;
        } else {
            if (isset($config['sections'][$selected_letter])) {
                $dbInfo = $config['sections'][$selected_letter];
            } else {
                $msgecho = "Invalid Call for your access.";
                exit;
            }
            $resultMessage = insertUserAndSection( $callsign, $role, $email, $selected_letter, $status);
            echo $resultMessage;
            try {
                $pdo = getPDOConnection($dbInfo);
                if ($pdo) {
                    // Check if the callsign already exists
                    $checkSql  = "SELECT 1 FROM tbl_Operator WHERE `Call` = :call";
                    $checkStmt = $pdo->prepare($checkSql);
                    $checkStmt->bindValue(':call', $callsign, PDO::PARAM_STR);
                    $checkStmt->execute();
                    $result = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($result) == 0) 
                    {   
                        echo "<div class='result'>";
                        echo "<h3>Submitted Data:</h3>";
                        echo "Callsign: $callsign<br>";
                        echo "Suffix: $suffix<br>";
                        echo "First Name: $first_name<br>";
                        echo "Last Name: $last_name<br>";
                        echo "Class: $class<br>";
                        echo "Start Date: $lic_issued<br>";
                        echo "Expiration Date: $lic_exp<br>";
                        echo "New Call: $new_call<br>";
                        echo "Old Call: $old_call<br>";
                        echo "Address: $address1<br>";
                        echo "Address 2: $address2<br>";
                        echo "City: $city<br>";
                        echo "State: $state<br>";
                        echo "Zip: $zip<br>";
                        //echo "Country: $country<br>";
                        echo "Email: $email<br>";
                        echo "Phone: $phone<br>";
                        echo "Born: " . ($dob !== null ? date("Y", strtotime($dob)) : "N/A") . "<br>";
                        echo "Custom Attress: $custom<br>";
                        echo "Role: $role<br>";
                        $sql = "INSERT INTO `tbl_Operator` (
                                    `Call`, `Status`, `PC/em date`, `Suffix`, `FirstName`, `LastName`, 
                                    `Address_1`, `Address_2`, `City`, `Zip`, `State`, `Phone`, 
                                    `Alt_Phone`, `Updated`, `Lic-exp`, `Lic-issued`, `DOB`, `E-Mail`, 
                                    `Class`, `Mail-Inst`, `NewCall`, `Remarks`, `Attachments`, `Mail-Label`, 
                                    `PC_Sent`, `Year-of-birth`, `Old_call`
                                ) 
                                VALUES (
                                    :call, :status, :pc_em_date, :suffix, :first_name, :last_name, 
                                    :address1, :address2, :city, :zip, :state, :phone, 
                                    :alt_phone, :updated, :lic_exp, :lic_issued, :dob, :email, 
                                    :class, :mail_inst, :new_call, :remarks, :attachments, :mail_label, 
                                    :pc_sent, :year_of_birth, :old_call
                                )";

                        $stmt = $pdo->prepare($sql);

                        if ($stmt) {
                            // Build the parameter array
                            $params = [
                                ':call'          => $callsign,
                                ':status'        => $status,
                                ':pc_em_date'    => $pc_em_date,
                                ':suffix'        => $suffix,
                                ':first_name'    => $first_name,
                                ':last_name'     => $last_name,
                                ':address1'      => $address1,
                                ':address2'      => $address2,
                                ':city'          => $city,
                                ':zip'           => $zip,
                                ':state'         => $state,
                                ':phone'         => $phone,
                                ':alt_phone'     => $alt_phone,
                                ':updated'       => $updated,
                                ':lic_exp'       => $lic_exp,
                                ':lic_issued'    => $lic_issued,
                                ':dob'           => $dob,
                                ':email'         => $email,
                                ':class'         => $class,
                                ':mail_inst'     => $mail_inst,
                                ':new_call'      => $new_call,
                                ':remarks'       => $remarks,
                                ':attachments'   => $attachments,
                                ':mail_label'    => $mail_label,
                                ':pc_sent'       => $pc_sent,
                                ':year_of_birth' => $year_of_birth,
                                ':old_call'      => $old_call,
                            ];

                            // Bind each parameter with the appropriate type
                            foreach ($params as $param => $value) {
                                $type = PDO::PARAM_STR;
                                if ($param === ':mail_label' || $param === ':pc_sent') {
                                    $type = PDO::PARAM_INT;
                                }
                                $stmt->bindValue($param, $value, $type);
                            }

                            if ($stmt->execute()) {
                                echo "New record created successfully";
                            } else {
                                $errorInfo = $stmt->errorInfo();
                                echo "Error inserting data: " . $errorInfo[2];
                            }
                        } else {
                            echo "Error preparing statement: " . $pdo->errorInfo()[2];
                        }
                    }
                else {
                    $msgecho = "User $callsign is already in the database.";
                    $callExists = true;
                }
            }
            } catch (PDOException $e) {
                $msgecho = "Database error: " . $e->getMessage();
                error_log("Database error: " . $e->getMessage());
            } catch (Exception $e) {
                $msgecho = "An error occurred: " . $e->getMessage();
                error_log("An error occurred: " . $e->getMessage());
            }
        }
    }
    ?>
</div>
<?php
// Include Java file (do not change to avoid breaking functionality)
$java = "$root/backend/java2.php";
include($java);

// Include footer
$footerPath = "$root/backend/footer.php";

include($footerPath);