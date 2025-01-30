<?php
/*... (License) */

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Login Page";
include("$root/backend/header.php");

$config = include("$root/config.php");

/**
 * Create a PDO connection.  (Your existing function with hardcoded dbname)
 */
function getPDOConnectionLogin(array $dbInfo) {
    try {
        $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['dbname']};charset=utf8";
        $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo; // Return the PDO object on success
    } catch (PDOException $e) {
        // Instead of die(), throw the exception so the calling code can handle it
        throw new PDOException("Database connection failed: " . $e->getMessage(), 0, $e);
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $pdo = getPDOConnectionLogin($config['db']); 

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username =:username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } catch (PDOException $e) {
        $error = "Database error: ". $e->getMessage();
    }
}?>

<div class="center-content">
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true):?>
        <div class="welcome-container">
            <img src="/7thArea.png" alt="7th Area" />
            <h2>Welcome, <?php echo strtoupper(htmlspecialchars($_SESSION['username']?? ''));?>!</h2>  <h2>You are logged in as a <?php echo htmlspecialchars($_SESSION['role']?? '');?>.</h2> </div>
    <?php else:?>
        <div class="login-container">
            <img src="/7thArea.png" alt="7th Area" />
            <h2>Login</h2>
            <?php if ($error):?>
                <div class="error-message"><?php echo htmlspecialchars($error);?></div>
            <?php endif;?>
            <form action="login.php" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    <?php endif;?>
</div>

<?php
include("$root/backend/footer.php");?>