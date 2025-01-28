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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        /* Reset default styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #fff;
            font-family: Arial, sans-serif;
        }

        .popup-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .popup-header {
            width: 100%;
            padding: 15px;
            background: #f0f0f0;
            border-bottom: 1px solid #ddd;
            text-align: center;
            position: relative;
        }

        .popup-content {
            padding: 20px;
            width: 100%;
            flex-grow: 1;
            overflow-y: auto;
        }

        .popup-footer {
            width: 100%;
            padding: 15px;
            background: #f0f0f0;
            border-top: 1px solid #ddd;
            text-align: center;
        }

        .btn {
            padding: 8px 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background: #0056b3;
        }

        h1 {
            font-size: 1.2em;
            margin: 0;
            color: #333;
        }
    </style>
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
        
            echo "Letter: " . $letter . "<br>";
            echo "Weight: " . $weight . "<br>";
        } else {
            echo "No data received.";
        }
        ?>
        <div class="popup-content">
            <?php 
            echo "Letter: " . htmlspecialchars($letter); 
            // Add your stamp selection content here
            ?>
        </div>
        <div class="popup-footer">
            <button class="btn" onclick="window.close()">Close</button>
        </div>
    </div>

    <script>
    // Prevent right-click context menu
    document.addEventListener('contextmenu', event => event.preventDefault());

    // Prevent keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Prevent F5, Ctrl+R (refresh)
        if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
            e.preventDefault();
        }
        // Prevent Alt+F4
        if (e.altKey && e.key === 'F4') {
            e.preventDefault();
        }
        // Prevent Ctrl+W (close window)
        if (e.ctrlKey && e.key === 'w') {
            e.preventDefault();
        }
    });

    // Prevent drag and drop
    document.addEventListener('dragstart', event => event.preventDefault());
    </script>
</body>
</html>
