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

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
// Initialize variables

$title = "Login Page";
$config = include('config.php');
include("$root/backend/header.php"); 


// Include the config file
$config = include('config.php');


// Initialize error message
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the username and password match
    if ($username === $config['credentials']['username'] && $password === $config['credentials']['password']) {
        // Set session variables
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;

        // Redirect to a protected page
        header('Location: topay.php');
        exit;
    } else {
        // Set error message
        $error = 'Invalid username or password.';
    }
}
?>


<div class="center-content">
        <div class="login-container">
            <img src="7thArea.png" alt="7th Area" />
            <h2>Login</h2>
            <form action="login.php" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
<?php
include("$root/backend/footer.php");
