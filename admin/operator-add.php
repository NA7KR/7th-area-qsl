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
<div class="container">
    <h1 class="center-content">Add New User</h1>
    <div class="form-wrapper">

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
                    </div>
    <div id="operator-add-container">
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
    if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['letter_select_form']) || $_POST['letter_select_form'] != 1)) {
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
        $selected_letter = htmlspecialchars($_POST['selected_letter'] ?? ' ');
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
        echo "Born: " . date("Y", strtotime($dob)) . "<br>";
        echo "Custom Attress: $custom<br>";
        echo "Role: $role<br>";
        echo "Selected Letter: $selected_letter<br>";
        echo "</div>";
        // Establish database connection
        $dbInfo = $config['sections'][$selectedLetter];
        $pdo    = getPDOConnection($dbInfo);

        if ($pdo) {
            // Check if the callsign already exists *FIRST*
            $checkSql = "SELECT 1 FROM tbl_Operator WHERE `Call` = :call"; // Correct column name
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindValue(':call', $callsign, PDO::PARAM_STR);
            $checkStmt->execute();
            $result = $checkStmt->fetchAll(PDO::FETCH_ASSOC); // Assign $result HERE, *before* the if condition
    
            if (count($result) > 0) {
                echo "Callsign already exists. Choose a different callsign or update the existing record.";
            } else {
                // Insert the data (only if the callsign doesn't exist)
                // ... (Your insert logic using prepared statements and the correct column names) ...
            }
        } else {
            echo "Database connection error!";
        }

        if (count($result) == 0) {
            // Debugging: Check values *before* calling insertData()
            var_dump($pdo, $callsign, $status, $pc_em_date, $suffix, $first_name, $last_name, $address1, $address2, $city, $zip, $state, $phone, $alt_phone, $updated, $lic_exp, $lic_issued, $dob, $email, $class, $mail_inst, $new_call, $remarks, $attachments, $mail_label, $pc_sent, $year_of_birth, $old_call, $custom, $role);


            $sql = "INSERT INTO `tbl_Operator` (`Call`, `Status`, `PC/em date`, `Suffix`, `FirstName`, `LastName`, `Address_1`, `Address_2`, `City`, `Zip`, `State`, `Phone`, `Alt_Phone`, `Updated`, `Lic-exp`, `Lic-issued`, `DOB`, `E-Mail`, `Class`, `Mail-Inst`, `NewCall`, `Remarks`, `Attachments`, `Mail-Label`, `PC_Sent`, `Year-of-birth`, `Old_call`) 
            VALUES (:call, :status, :pc_em_date, :suffix, :first_name, :last_name, :address1, :address2, :city, :zip, :state, :phone, :alt_phone, :updated, :lic_exp, :lic_issued, :dob, :email, :class, :mail_inst, :new_call, :remarks, :attachments, :mail_label, :pc_sent, :year_of_birth, :old_call)";
        
            $stmt = $pdo->prepare($sql);

            if ($stmt) {
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
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const submitButton = document.getElementById('submitButton');
    const fetchButton = document.getElementById('fetchButton');
    const clearButton = document.getElementById('clearButton');
    const dataForm = document.getElementById('dataForm');
    const customAddressCheckbox = document.getElementById('customAddress');

    function fetchQRZData() {
        const callsign = document.getElementById('callsign').value;
        if (!callsign) {
            alert('Please enter a callsign to fetch data from QRZ.');
            return;
        }

        const apikey = <?php echo json_encode($config['qrz_api']['key']); ?>;
        const apicall = <?php echo json_encode($config['qrz_api']['callsign']); ?>;

        const initialUrl = `https://xmldata.qrz.com/xml/current/?username=${apicall}&password=${apikey}`;

        fetch(initialUrl)
            .then(response => response.text())
            .then(data => {
                const sessionKey = extractSessionKey(data);
                if (sessionKey) {
                    fetchCallsignData(sessionKey, callsign);
                } else {
                    console.error('Failed to retrieve session key.');
                }
            })
            .catch(error => {
                console.error('Error fetching initial data:', error);
            });

        function extractSessionKey(xml) {
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xml, "text/xml");
            const keyNode = xmlDoc.getElementsByTagName("Key")[0];
            if (keyNode) {
                return keyNode.textContent.trim();
            } else {
                const errorNode = xmlDoc.getElementsByTagName("Error")[0];
                if (errorNode) {
                    alert(`Error: ${errorNode.textContent.trim()}`);
                }
                return null;
            }
        }

        function fetchCallsignData(sessionKey, callsign) {
            const url = `https://xmldata.qrz.com/xml/current/?s=${sessionKey};callsign=${callsign}`;

            fetch(url)
                .then(response => response.text())
                .then(data => {
                    console.log("QRZ Data:", data);
                    parseXML(data);
                })
                .catch(error => {
                    console.error('Error fetching callsign data:', error);
                });
        }

        function parseXML(xml) {
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xml, "text/xml");

            const error = xmlDoc.getElementsByTagName("Error")[0];
            if (error) {
                alert(`Error: ${error.textContent}`);
                return;
            }

            function removeInitials(name) {
                return name.replace(/(?:\s[A-Z]\.?)+$/, '').trim();
            }

            const firstNameNode = xmlDoc.getElementsByTagName("fname")[0];
            if (firstNameNode) {
                document.getElementById('first_name').value = removeInitials(firstNameNode.textContent.trim());
            }

            const callNode = xmlDoc.getElementsByTagName("call")[0];
            if (callNode) {
                document.getElementById('callsign').value = callNode.textContent.trim();
            }

            const lastNameNode = xmlDoc.getElementsByTagName("name")[0];
            if (lastNameNode) {
                document.getElementById('last_name').value = lastNameNode.textContent.trim();
            }

            const addr1Node = xmlDoc.getElementsByTagName("addr1")[0];
            if (addr1Node) {
                document.getElementById('address').value = addr1Node.textContent.trim();
            }

            const addr2Node = xmlDoc.getElementsByTagName("addr2")[0];
            if (addr2Node) {
                document.getElementById('city').value = addr2Node.textContent.trim();
            }

            const stateNode = xmlDoc.getElementsByTagName("state")[0];
            if (stateNode) {
                document.getElementById('state').value = stateNode.textContent.trim();
            }

            const zipNode = xmlDoc.getElementsByTagName("zip")[0];
            if (zipNode) {
                document.getElementById('zip').value = zipNode.textContent.trim();
            }

            const countryNode = xmlDoc.getElementsByTagName("country")[0];
            if (countryNode) {
                document.getElementById('country').value = countryNode.textContent.trim();
            }

            const efdateNode = xmlDoc.getElementsByTagName("efdate")[0];
            if (efdateNode) {
                document.getElementById('date_start').value = efdateNode.textContent.trim();
            }

            const expdateNode = xmlDoc.getElementsByTagName("expdate")[0];
            if (expdateNode) {
                document.getElementById('date_exp').value = expdateNode.textContent.trim();
            }

            const classNode = xmlDoc.getElementsByTagName("class")[0];
            if (classNode) {
                const classMapping = {
                    'E': 'Extra',
                    'G': 'General',
                    'T': 'Technician',
                    'A': 'Advanced',
                    'C': 'Club'
                };
                document.getElementById('class').value = classMapping[classNode.textContent.trim()] || classNode.textContent.trim();
            }

            const emailNode = xmlDoc.getElementsByTagName("email")[0];
            if (emailNode) {
                document.getElementById('email').value = emailNode.textContent.trim();
            }

            const bornNode = xmlDoc.getElementsByTagName("born")[0];
            if (bornNode) {
                document.getElementById('born').value = bornNode.textContent.trim();
            }
              // After populating fields, ALWAYS enable the submit button
            submitButton.disabled = false;
            submitButton.classList.remove('disabled-button');

            // Since data was fetched, disable the fetch button
            fetchButton.disabled = true;
            fetchButton.classList.add('disabled-button');
        }
    }

    function updateButtonStates() {
        if (customAddressCheckbox.checked) {
            submitButton.disabled = false;
            submitButton.classList.remove('disabled-button');
            fetchButton.disabled = true;
            fetchButton.classList.add('disabled-button');
        } else {
            submitButton.disabled = true;
            submitButton.classList.add('disabled-button');
            fetchButton.disabled = false;
            fetchButton.classList.remove('disabled-button');
        }
    }

    customAddressCheckbox.addEventListener('change', updateButtonStates);
    fetchButton.addEventListener('click', fetchQRZData);
    clearButton.addEventListener('click', function () {
        dataForm.reset();
        customAddressCheckbox.checked = false;
        updateButtonStates();
    });

    updateButtonStates();

});
</script>


<?php
$footerPath = "$root/backend/footer.php";
if (file_exists($footerPath)) {
    include($footerPath);
} else {
    echo "Footer file not found!";
}
?>
</body>
</html>
