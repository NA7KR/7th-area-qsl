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
$title = 'Stamps';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
 
</head>
<body>
    <div class="popup-container">
        <div class="popup-header">
            <h1>Select Stamps Required</h1>
        </div>
        <?php 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Retrieve POST parameters
            $letter = htmlspecialchars($_POST['letter'] ?? 'N/A'); // Sanitize and check if it exists
            $weight = htmlspecialchars($_POST['weight'] ?? 'N/A'); // Sanitize and check if it exists
        
            //echo "Letter: " . $letter . "<br>";
            //echo "Weight: " . $weight . "<br>";
        } else {
            //echo "No data received.";
        }
        print_r($_POST);
        ?>
        <div class="popup-content">
            <?php 
            //echo "Letter: " . htmlspecialchars($letter); 
            // Add your stamp selection content here
            ?>
        </div>
        <div class="popup-footer">
            <button class="btn" onclick="window.close()">Close</button>
        </div>
    </div>


</body>
</html>
