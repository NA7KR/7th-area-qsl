<?php
session_start();

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Page to Delete Operator";
$config = include('config.php');
include("$root/backend/header.php"); 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
?>



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
