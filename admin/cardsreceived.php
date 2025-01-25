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

#session_start();
$root = realpath($_SERVER["DOCUMENT_ROOT"]);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Cards Received";
$selectedLetter   = null;

// Ensure user is logged in
#if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
#    header('Location: login.php');
#    exit;
#}

include("$root/backend/header.php");
$config = include($root . '/config.php');

/**
 * If the form has been submitted, retrieve the POST data.
 * You can then handle it, e.g., insert into a database, update a file, etc.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedLetter = $_POST['letter'] ?? null;
    $ID            = $_POST['ID'] ?? '';
    $Call          = $_POST['Call'] ?? '';
    $CardsReceived = $_POST['CardsReceived'] ?? '';
    $DateReceived  = $_POST['DateReceived'] ?? '';
    $NewCall       = $_POST['NewCall'] ?? '';
    $Status        = $_POST['Status'] ?? '';
    $MailInst      = $_POST['Mail-Inst'] ?? '';

    // Example: Insert/update your database here
    // ...
}
?>

<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau - Cards Received</h1>

    <!-- Use htmlspecialchars($_SERVER['PHP_SELF']) for security -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter" class="form-control">
            <option value="F" <?php echo ($selectedLetter === 'F') ? 'selected' : ''; ?>>F</option>
            <option value="O" <?php echo ($selectedLetter === 'O') ? 'selected' : ''; ?>>O</option>
        </select>
        
        <button type="submit" >Select</button>
</form>  
        
        
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 10px; width: 400px; padding: 10px; border: 1px ;">
    <!-- ID -->
     
    <label for="ID" style="text-align: right; font-weight: bold;">ID:</label>
    <input type="text" id="ID" name="ID" required 
           value="<?php echo isset($ID) ? htmlspecialchars($ID) : ''; ?>" style="width: 100%;">

    <!-- Call -->
    <label for="Call" style="text-align: right; font-weight: bold;">Call:</label>
    <input type="text" id="Call" name="Call" required 
           value="<?php echo isset($Call) ? htmlspecialchars($Call) : ''; ?>" style="width: 100%;">

    <!-- Cards Received -->
    <label for="CardsReceived" style="text-align: right; font-weight: bold;">Cards Received:</label>
    <input type="text" id="CardsReceived" name="CardsReceived" required 
           value="<?php echo isset($CardsReceived) ? htmlspecialchars($CardsReceived) : ''; ?>" style="width: 100%;">

    <!-- Date Received -->
    <label for="DateReceived" style="text-align: right; font-weight: bold;">Date Received:</label>
    <input type="text" id="DateReceived" name="DateReceived" required 
           value="<?php echo isset($DateReceived) ? htmlspecialchars($DateReceived) : ''; ?>" style="width: 100%;">

    <!-- New Call -->
    <label for="NewCall" style="text-align: right; font-weight: bold;">New Call:</label>
    <input type="text" id="NewCall" name="NewCall" required 
           value="<?php echo isset($NewCall) ? htmlspecialchars($NewCall) : ''; ?>" style="width: 100%;">

    <!-- Status -->
    <label for="Status" style="text-align: right; font-weight: bold;">Status:</label>
    <select id="Status" name="Status" style="width: 100%;">
        <option>Select a status</option>
        <!-- Add options here -->
    </select>

    <!-- Mail Instruction -->
    <label for="MailInst" style="text-align: right; font-weight: bold;">Mail-Inst:</label>
    <select id="MailInst" name="MailInst" style="width: 100%;">
        <option>Select mail instruction</option>
        <!-- Add options here -->
    </select>

    <!-- Submit Button -->
    <div style="grid-column: 1 / -1; text-align: center; margin-top: 10px;">
        <button type="submit" style="background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
            Submit
        </button>
    </div>
</div>

<?php

include("$root/backend/footer.php");
?>
