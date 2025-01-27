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
