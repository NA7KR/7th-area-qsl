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
session_unset();
session_destroy();
if (!isset($_SESSION['loggedin'])) {
    header("Refresh: 15; url=index.php"); // Redirect to index.php after 30 seconds
}

$root = realpath($_SERVER["DOCUMENT_ROOT"]);


$title = "Total Cards Received";
include("$root/backend/header.php");
$config = include($root . '/config.php');
?>
    <div class="center-content">
    
        <img src="/7thArea.png" alt="7th Area" />

        <h2>You have been logged out.</h2>
        <a href="login.php">Login Again</a>
    </div>
</body>
</html>
<?php
include("$root/backend/footer.php");