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

if (!isset($_SESSION['submittedData']) || empty($_SESSION['submittedData'])) {
    echo "No data to print.";
    exit;
}

$submittedData = $_SESSION['submittedData'];
unset($_SESSION['submittedData']); // Clear the session data after use
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        @media print {
            #PrintButton {
                display: none;
            }
            body {
                font-size: 22pt;
            }
            @page {
                size: 180mm 55mm;
                margin: .1mm .1mm .1mm 10mm;
            }
            .label {
                margin-bottom: 20px; /* Space between labels */
                page-break-inside: avoid; /* Prevents page breaks within a label */
            }
        }
    </style>
</head>
<body>
    <?php foreach ($submittedData as $row): ?>
        <div class="label">
            <strong> <?= htmlspecialchars($row['Call'])?></strong><br>
            <?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?><br>
            <?= htmlspecialchars($row['Address']) ?><br>
            <?= htmlspecialchars($row['City'] ) ?><br>
            <?= htmlspecialchars($row['State'] . ' ' . $row['Zip']) ?>
        </div>
    <?php endforeach; ?>

    <center><button id="PrintButton" onclick="PrintPage()">Print</button></center>
</body>
<script type="text/javascript">
    function PrintPage() {
        window.print();
    }

    function redirectToTopay() {
        window.location.href = "topay.php"; // Redirects to topay.php after printing
    }

    window.addEventListener('DOMContentLoaded', (event) => {
        PrintPage();
        setTimeout(redirectToTopay, 500); // Give time for the print dialog to appear
    });
</script>
</html>
