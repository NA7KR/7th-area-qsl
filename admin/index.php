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

// Secure session settings
session_set_cookie_params([
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true, // Ensure cookie is sent over HTTPS
    'httponly' => true, // Prevent JavaScript access to session cookie
    'samesite' => 'Strict' // Prevent CSRF by disallowing third-party sites to send cookies
]);

session_start();

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Login Page";
include("$root/backend/header.php"); 

// Include the config file
$config = include("$root/config.php");

// Initialize error message
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verify the username and password
    if (isset($config['credentials'][$username])) {
        $storedPasswordHash = $config['credentials'][$username];

        if (password_verify($password, $storedPasswordHash)) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;

            // Redirect to a protected page
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Invalid username or password.';
    }
}
?>

<div class="center-content">
    <div class="login-container">
        <img src="/7thArea.png" alt="7th Area" />

        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
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
?>
