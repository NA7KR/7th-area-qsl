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
//print_r($_POST);
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Page to Add Operator ";
$config = include('config.php');
include("$root/backend/header.php"); 
$selectedLetter = null;

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
$selectedLetter = $_SESSION['selected_letter'] ?? ''; // From session or empty

// Handle the first form submission (letter selection)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['letter_select_form']) && $_POST['letter_select_form'] == 1) {
    if (isset($_POST['letter'])) {
        $_SESSION['selected_letter'] = $_POST['letter'];
        // Redirect to prevent resubmission on refresh (Important!)
        header("Location: " . $_SERVER['PHP_SELF']); 
        exit;
    }
}

$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$role = $_SESSION['role'] ?? 'Admin';
$available_roles = ['User', 'Admin', 'Ops']; 

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
?>
<div class="center-content">
<img src="/7thArea.png" alt="7th Area" />

</div>

  
<div id="operator-add-container">
<h1 class="center-content">Add New User</h1>
    <div class="form-wrapper">
    <form method="post" id="dataForm">

<div style="display: flex; align-items: center;">
    <label for="callsign" style="margin-right: 10px; white-space: nowrap;">Callsign:</label>
    <input type="text" id="callsign" name="callsign" required>
</div>

<div style="display: flex; align-items: center;">
    <label for="suffix" style="margin-right: 10px; white-space: nowrap;">Suffix:</label>
    <input type="text" id="suffix" name="suffix" >
</div>

<div style="display: flex; align-items: center;">
    <label for="first_name" style="margin-right: 10px; white-space: nowrap;">First Name:</label>
    <input type="text" id="first_name" name="first_name" required>
</div>

<div style="display: flex; align-items: center;">
    <label for="last_name" style="margin-right: 10px; white-space: nowrap;">Last Name:</label>
    <input type="text" id="last_name" name="last_name" required>
</div>

<div style="display: flex; align-items: center;">
    <label for="class" style="margin-right: 10px; white-space: nowrap;">Class:</label>
    <input type="text" id="class" name="class">
</div>

<div style="display: flex; align-items: center;">
    <label for="date_start" style="margin-right: 10px; white-space: nowrap;">Start Date:</label>
    <input type="text" id="date_start" name="date_start">
</div>

<div style="display: flex; align-items: center;">
    <label for="date_exp" style="margin-right: 10px; white-space: nowrap;">Expiration Date:</label>
    <input type="text" id="date_exp" name="date_exp">
</div>

<div style="display: flex; align-items: center;">
    <label for="new_call" style="margin-right: 10px; white-space: nowrap;">New Call:</label>
    <input type="text" id="new_call" name="new_call">
</div>

<div style="display: flex; align-items: center;">
    <label for="old_call" style="margin-right: 10px; white-space: nowrap;">Old Call:</label>
    <input type="text" id="old_call" name="old_call">
</div>

<div style="display: flex; align-items: center;" class="full-width">
    <label for="address" style="margin-right: 10px; white-space: nowrap;">Address:</label>
    <input type="text" id="address" name="address" class="full-width-input">
</div>

<div style="display: flex; align-items: center;" class="full-width">
    <label for="address2" style="margin-right: 10px; white-space: nowrap;">Address 2:</label>
    <input type="text" id="address2" name="address2" class="full-width-input">
</div>

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

<div style="display: flex; align-items: center;">
    <label for="country" style="margin-right: 10px; white-space: nowrap;">Country:</label>
    <input type="text" id="country" name="country">
</div>

<div style="display: flex; align-items: center;">
    <label for="email" style="margin-right: 10px; white-space: nowrap;">Email:</label>
    <input type="email" id="email" name="email">
</div>

<div style="display: flex; align-items: center;">
    <label for="phone" style="margin-right: 10px; white-space: nowrap;">Phone:</label>
    <input type="tel" id="phone" name="phone">
</div>

<div style="display: flex; align-items: center;">
    <label for="born" style="margin-right: 10px; white-space: nowrap;">Born:</label>
    <input type="text" id="born" name="born">
</div>

<?php if ($role == 'Admin'): ?>
    <div class="full-width" style="display: flex; align-items: center;"> 
    <label for="customAddress" style="margin-right: 10px; white-space: nowrap;">Status</label>
    <select name="customAddress" id="customAddress">
  <option value="Active">Active</option>
  <option value="Custom Address">Custom Address</option>
  <option value="New">New</option>
  <option value="Via">Via</option>
</select>
</div>

<?php else: ?>
<div class="full-width" style="display: flex; align-items: center;"> 
    <label for="customAddress" style="margin-right: 10px; white-space: nowrap;">Custom Address</label>
    <input type="checkbox" id="customAddress" name="customAddress">
</div>
<?php endif; ?>

<?php if ($role == 'Admin'): ?>
    <div style="display: flex; align-items: center;">
        <label for="role" style="margin-right: 10px; white-space: nowrap;">Role:</label>
        <select id="role" name="role">
            <?php foreach ($available_roles as $available_role): ?>
                <option value="<?php echo $available_role; ?>" <?php if ($available_role == 'User') echo 'selected'; ?> >
                    <?php echo $available_role; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
<?php endif; ?>
</div>
<input type="hidden" name="selected_letter" value="<?php echo htmlspecialchars($selectedLetter); ?>"><br>

<div class="full-width" style="display: flex; align-items: center; justify-content: center;">
    <input type="submit" value="Submit" id="submitButton" disabled>
</div><br>

<div class="full-width" style="display: flex; align-items: center; justify-content: center;">
    <button type="button" id="fetchButton">Fetch Data from QRZ</button>
</div><br>

<div class="full-width" style="display: flex; align-items: center; justify-content: center;">
    <button type="button" id="clearButton">Clear</button>
</div>

</form>
            </div>


    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['letter_select_form'])) ) {
        $callsign = htmlspecialchars($_POST['callsign']);
        $first_name = htmlspecialchars($_POST['first_name']);
        $last_name = htmlspecialchars($_POST['last_name']);
        $class = htmlspecialchars($_POST['class']);
        $lic_issued = htmlspecialchars($_POST['date_start']);
        $lic_exp  = htmlspecialchars($_POST['date_exp']);
        $new_call = htmlspecialchars($_POST['new_call']);
        $old_call = htmlspecialchars($_POST['old_call']);
        $address1 = htmlspecialchars($_POST['address']);
        $address2 = htmlspecialchars($_POST['address2']);
        $city = htmlspecialchars($_POST['city']);
        $state = htmlspecialchars($_POST['state']);
        $zip = htmlspecialchars($_POST['zip']);
        $country = htmlspecialchars($_POST['country']);
        $email = htmlspecialchars($_POST['email']);
        $phone = htmlspecialchars($_POST['phone']);
        $dob = htmlspecialchars($_POST['born'] ?? null);
        if ($dob !== null) {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dob)) { // YYYY-MM-DD HH:MM:SS format
                $datetime = $dob; // Use as is
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) { // YYYY-MM-DD format
                $datetime = $dob . ' 00:00:00'; // Add default time (midnight)
            } elseif (preg_match('/^\d{4}$/', $dob)) { // YYYY format (from QRZ)
                $datetime = $dob . '-01-01 00:00:00'; // Default to Jan 1st, midnight - THIS IS THE KEY CHANGE
            } else {
                $datetime = null; // Invalid format
            }
        }
        $dob = $datetime;
        $custom = htmlspecialchars($_POST['customAddress'] ?? 'Off'); 
        $role = htmlspecialchars($_POST['role'] ?? 'User');
        
        $pc_em_date = null;
        $suffix  = htmlspecialchars($_POST['suffix'] ?? null);
        $alt_phone  = null;
        $updated = Date("Y-m-d");
        $mail_inst  = null;
        $remarks = null;
        $attachments = null;
        $mail_label = null;
        $pc_sent    = null;
        $status = null;
        $year_of_birth = null;


        echo "<div class='result'>";
        echo "<h3>Submitted Data:</h3>";
        echo "Callsign: $callsign<br>";
        echo "Suffix: $suffix<br>";
        echo "First Name: $first_name<br>";
        echo "Last Name: $last_name<br>";
        echo "Class: $class<br>";
        echo "Start Date: $lic_issued<br>";
        echo "Expiration Date: $lic_exp <br>";
        echo "New Call: $new_call<br>";
        echo "Old Call: $old_call<br>";
        echo "Address: $address1<br>";
        echo "Address 2: $address2<br>";
        echo "City: $city<br>";
        echo "State: $state<br>";
        echo "Zip: $zip<br>";
        echo "Country: $country<br>";
        echo "Email: $email<br>";
        echo "Phone: $phone<br>";
        echo "Born: " ;
        if ($dob !== null) {  // Check if $dob is not null before using it
            echo date("Y", strtotime($dob));
        } else {
            echo "N/A"; // Or any other placeholder you want
        }
        echo "<br>";
        echo "Custom Attress: $custom<br>";
        echo "Role: $role<br>";
        $selected_letter = getFirstLetterAfterNumber($callsign) ;
        echo "Selected Letter: $selected_letter<br>";
        
        if (str_starts_with($selected_letter, "Error:")) { // Use $selected_letter consistently
            
            exit;
        } else {
          
            
            if (isset($config['sections'][$selected_letter])) {
                $dbInfo = $config['sections'][$selected_letter];
            } else {
                echo "Invalid Call for your access.";
                exit;
            }
            try {
                $pdo = getPDOConnection($dbInfo);
            
                if ($pdo) {
                    // Check if the callsign already exists *FIRST*
                    $checkSql = "SELECT 1 FROM tbl_Operator WHERE `Call` = :call"; // Correct column name
                    $checkStmt = $pdo->prepare($checkSql);
                    $checkStmt->bindValue(':call', $callsign, PDO::PARAM_STR);
                    $checkStmt->execute();
                    $result = $checkStmt->fetchAll(PDO::FETCH_ASSOC); // Assign $result HERE, *before* the if condition
            
                   

            if (count($result) == 0) 
            {
                // Debugging: Check values *before* calling insertData()
                var_dump($pdo, $callsign, $status, $pc_em_date, $suffix, $first_name, $last_name, $address1, $address2, $city, $zip, $state, $phone, $alt_phone, $updated, $lic_exp, $lic_issued, $dob, $email, $class, $mail_inst, $new_call, $remarks, $attachments, $mail_label, $pc_sent, $year_of_birth, $old_call, $custom, $role);


                $sql = "INSERT INTO `tbl_Operator` (`Call`, `Status`, `PC/em date`, `Suffix`, `FirstName`, `LastName`, `Address_1`, `Address_2`, `City`, `Zip`, `State`, `Phone`, `Alt_Phone`, `Updated`, `Lic-exp`, `Lic-issued`, `DOB`, `E-Mail`, `Class`, `Mail-Inst`, `NewCall`, `Remarks`, `Attachments`, `Mail-Label`, `PC_Sent`, `Year-of-birth`, `Old_call`) 
                VALUES (:call, :status, :pc_em_date, :suffix, :first_name, :last_name, :address1, :address2, :city, :zip, :state, :phone, :alt_phone, :updated, :lic_exp, :lic_issued, :dob, :email, :class, :mail_inst, :new_call, :remarks, :attachments, :mail_label, :pc_sent, :year_of_birth, :old_call)";
            
                $stmt = $pdo->prepare($sql);

                if ($stmt) 
                {
                    $stmt->bindValue(':call', $callsign, PDO::PARAM_STR); // Correct column name!
                    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
                    $stmt->bindValue(':pc_em_date', $pc_em_date, PDO::PARAM_STR);
                    $stmt->bindValue(':suffix', $suffix, PDO::PARAM_STR);
                    $stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
                    $stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
                    $stmt->bindValue(':address1', $address1, PDO::PARAM_STR);
                    $stmt->bindValue(':address2', $address2, PDO::PARAM_STR);
                    $stmt->bindValue(':city', $city, PDO::PARAM_STR);
                    $stmt->bindValue(':zip', $zip, PDO::PARAM_STR);
                    $stmt->bindValue(':state', $state, PDO::PARAM_STR);
                    $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
                    $stmt->bindValue(':alt_phone', $alt_phone, PDO::PARAM_STR);
                    $stmt->bindValue(':updated', $updated, PDO::PARAM_STR);
                    $stmt->bindValue(':lic_exp', $lic_exp, PDO::PARAM_STR);
                    $stmt->bindValue(':lic_issued', $lic_issued, PDO::PARAM_STR);
                    $stmt->bindValue(':dob', $dob, PDO::PARAM_STR);
                    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $stmt->bindValue(':class', $class, PDO::PARAM_STR);
                    $stmt->bindValue(':mail_inst', $mail_inst, PDO::PARAM_STR);
                    $stmt->bindValue(':new_call', $new_call, PDO::PARAM_STR);
                    $stmt->bindValue(':remarks', $remarks, PDO::PARAM_STR);
                    $stmt->bindValue(':attachments', $attachments, PDO::PARAM_STR);
                    $stmt->bindValue(':mail_label', $mail_label, PDO::PARAM_INT);
                    $stmt->bindValue(':pc_sent', $pc_sent, PDO::PARAM_INT);
                    $stmt->bindValue(':year_of_birth', $year_of_birth, PDO::PARAM_STR);
                    $stmt->bindValue(':old_call', $old_call, PDO::PARAM_STR);

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
            } 
        }
            catch (PDOException $e) 
            {
            echo "Database error: " . $e->getMessage(); // Display the error message
            // Optionally log the error for debugging:
            error_log("Database error: " . $e->getMessage()); 
            } 
            catch (Exception $e) { // Catch any other potential exceptions
            echo "An error occurred: " . $e->getMessage();
            error_log("An error occurred: " . $e->getMessage());
        }
    }
}
    ?>
</div>
<?php

$java = "$root/backend/java2.php";
if (file_exists($java)) {
    include($java);
} else {
    echo "Java2 file not found!";
}

$footerPath = "$root/backend/footer.php";
if (file_exists($footerPath)) {
    include($footerPath);
} else {
    echo "Footer file not found!";
}
?>
</body>
</html>
