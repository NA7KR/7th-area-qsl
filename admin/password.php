<?php
// Prompt the user for a password
echo "Enter a password: ";
$password = trim(fgets(STDIN));

// Validate password (optional, you can add rules here)
if (strlen($password) < 8) {
    echo "Password must be at least 8 characters long.\n";
    exit(1);
}

// Hash the password using the default algorithm (BCRYPT)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Output the hashed password
echo "Hashed Password: " . $hashedPassword . "\n";

// Optionally, you can store this in a configuration file or database
?>
