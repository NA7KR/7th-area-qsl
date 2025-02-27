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

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Change Password";
include("$root/backend/header.php");
$config = include("$root/config.php");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } else {
        try {
            $pdo = getPDOConnectionLogin($config['db']);
            $stmt = $pdo->prepare("SELECT password FROM users WHERE username = :username");
            $stmt->execute([':username' => $_SESSION['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($currentPassword, $user['password'])) {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = :password, ChangePassword = 0 WHERE username = :username");
                $stmt->execute([
                    ':password' => $newPasswordHash, 
                    ':username' => $_SESSION['username']
                ]);
                
                $success = 'Password successfully changed.';
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

?>

<div class="center-content">
    <div class="password-change-container">
        <h2>Change Password</h2>
        <h2><p>Logged in as: <?php echo strtoupper(htmlspecialchars($_SESSION['username'])); ?></p></h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="form-group full-width">
        <label for="current_password">Current Password:</label>
        <input type="password" id="current_password" name="current_password" required>
    </div>
    <div class="form-group full-width">
        <label for="new_password">New Password:</label>
        <input type="password" id="new_password" name="new_password" required>
    </div>
    <div class="form-group full-width">
        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div>
    <button type="submit">Change Password</button>
</form>

    </div>
</div>

<?php
include("$root/backend/footer.php");
?>
