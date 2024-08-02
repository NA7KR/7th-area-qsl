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
ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$footertext = "NA7KR";

?>
</div>
<div style="text-align: center;">
    <footer>
        <!-- Display the copyright and last modified information -->
        <p>&copy; <?= date("Y"); ?> <?php echo $footertext; ?> </p>
        <p>Last updated: <?= date("F d Y H:i:s.", getlastmod()); ?></p>
    </footer>
</div>

<?php
// Include java.php
include($root . '/backend/java.php');
?>

</body>
</html>