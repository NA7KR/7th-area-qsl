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

function getStatusByCallAndLetter($call, $letter, $config)
{ 
    
    
    $pdo = getPDOConnectionLogin($config['db']); 
    // Pass the $config array
    try {
        $pdo = getPDOConnectionLogin($config['db']); // Use your PDO connection function

        $stmt = $pdo->prepare("SELECT `status` FROM sections WHERE `call` = :call AND `letter` = :letter");
        $stmt->execute([':call' => $call, ':letter' => $letter]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['status'];
        } else {
            return null; // Not found
        }
    } catch (PDOException $e) {
        // Handle the error (log it, display a message, etc.)
        error_log("PDO error: " . $e->getMessage());
        return false; // Error indicator
    } finally {
       //Close the connection
        $pdo = null;
    }
}


?>