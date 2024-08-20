<?php
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
            <strong>Callsign:</strong> <?= htmlspecialchars($row['Call']) ?><br>
            <?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?><br>
            <?= htmlspecialchars($row['Address']) ?><br>
            <?= htmlspecialchars($row['City'] . ', ' . $row['State'] . ' ' . $row['Zip']) ?><br><br>
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
