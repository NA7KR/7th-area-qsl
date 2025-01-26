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

$letter = $_POST['letter'] ?? ''; // Original letter value
$call   = $_POST['call'] ?? '';
$cards  = $_POST['CardsReceived'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Submission Successful</title>
</head>
<body>
    <h2>Cards Received Updated</h2>
    <p>
        <strong>Letter:</strong> <?php echo htmlspecialchars($letter); ?><br>
    </p>
    <p style="color: green;">Data was successfully submitted!</p>
    <p>You will be redirected back in 5 seconds...</p>
</body>
<script>
    // Wait 5 seconds, then submit a POST request
    setTimeout(() => {
        // Create a form element dynamically
        const form = document.createElement("form");
        form.method = "POST"; // POST request
        form.action = "../admin/cardsreceived.php"; // The target URL for POST redirection

        // Add hidden input fields for POST data
        const formInput = document.createElement("input");
        formInput.type = "hidden";
        formInput.name = "letter_select_form";
        formInput.value = "1"; // Static value
        form.appendChild(formInput);

        const letterInput = document.createElement("input");
        letterInput.type = "hidden";
        letterInput.name = "letter";
        letterInput.value = "<?php echo htmlspecialchars($letter, ENT_QUOTES, 'UTF-8'); ?>"; // Dynamically send the letter
        form.appendChild(letterInput);

        // Append the form to the body and submit it
        document.body.appendChild(form);
        form.submit();
    }, 5000); // 5000 milliseconds = 5 seconds
</script>
</html>
