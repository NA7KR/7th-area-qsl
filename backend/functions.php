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
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$config = include($root . '/config.php');
  
/**
 * Create a PDO connection using config array: ['host','dbname','username','password'].
 * 
 * @param array $dbInfo An associative array containing database connection details:
 *                      - 'host': The hostname of the database server.
 *                      - 'dbname': The name of the database.
 *                      - 'username': The username for the database connection.
 *                      - 'password': The password for the database connection.
 * @return PDO The PDO instance representing the database connection.
 * @throws PDOException If the connection to the database fails.
 */
function getPDOConnection(array $dbInfo)
{
    try {
        $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['dbname']};charset=utf8";
        $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Get the next available ID from the tbl_CardM table.
 * 
 * This function queries the tbl_CardM table to find the maximum ID value and returns the next available ID.
 * 
 * @param PDO $pdo The PDO instance representing the database connection.
 * @return int The next available ID.
 */
function getNextID(PDO $pdo)
{
    try {
        $stmt = $pdo->query("SELECT MAX(ID) as maxID FROM tbl_CardM");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['maxID'] ?? 0) + 1;
    } catch (PDOException $e) {
        error_log("Error getting next ID: " . $e->getMessage());
        return 1;
    }
}

/**
 * Fetch all rows from a specified table, optionally with date filtering on $dateColumn.
 * 
 * This function retrieves all rows from the specified table, optionally filtering by date range if $startDate and $endDate are provided.
 * 
 * @param PDO $pdo The PDO instance representing the database connection.
 * @param string $tableName The name of the table to fetch rows from.
 * @param string|null $startDate The start date for filtering rows (optional).
 * @param string|null $endDate The end date for filtering rows (optional).
 * @param string|null $dateColumn The name of the date column to filter by (optional).
 * @return array An array of associative arrays representing the fetched rows.
 * @throws PDOException If the query execution fails.
 */
function fetchStamps(PDO $pdo, $tableName, $startDate = null, $endDate = null, $dateColumn = null)
{
    $query = "SELECT * FROM `$tableName`";
    $params = [];

    if ($dateColumn && $startDate && $endDate) {
        $query .= " WHERE `$dateColumn` BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate'] = $endDate;
    }

    $query .= " ORDER BY `ID` ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error retrieving stamp data: " . $e->getMessage());
    }
}

/**
 * Fetch all rows from tbl_Stamps, optionally with date filtering on $dateColumn.
 * We do NOT do grouping in SQL here, so we can apply config-based values in PHP.
 */
function fetchAllStamps(PDO $pdo, $tableName, $startDate = null, $endDate = null, $dateColumn = null)
{
    $query = "SELECT * FROM `$tableName`";
    $params = [];

    if ($dateColumn && $startDate && $endDate) {
        $query .= " WHERE `$dateColumn` BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate']   = $endDate;
    }

    // Maybe sort by date or ID, up to you
    $query .= " ORDER BY `ID` ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error retrieving stamp data: " . $e->getMessage());
    }
}

/**
 * Group rows by `Value` in PHP, summing QTY Purchased/Used, 
 * then calculating "Stamps On Hand", "Total Purchased", "Cost of Postage"
 * using config-based stamp values (if any).
 */
function parseAndAggregate(array $rows, array $config): array
{
    // We'll accumulate into $aggregated[$valueName].
    $aggregated = [];

    foreach ($rows as $r) {
        // The columns have spaces, so we must reference them in $r exactly:
        $valueRaw       = $r['Value']            ?? ''; // e.g. "Forever", "Additional Ounce", "0.01"
        $qtyPurchased   = $r['QTY Purchased']    ?? 0;  // int
        $qtyUsed        = $r['QTY Used']         ?? 0;  // int

        // Convert them to int
        $qtyPurchased   = (int)$qtyPurchased;
        $qtyUsed        = (int)$qtyUsed;

        // If config has a custom numeric for this value
        // e.g. $config['stamps']['Forever'] = 0.63
        if (isset($config['stamps'][$valueRaw])) {
            $stampNumericValue = (float)$config['stamps'][$valueRaw];
        } else {
            // Attempt to parse the string as a float (like "0.01")
            // If not numeric, it becomes 0
            $stampNumericValue = (float)$valueRaw;
        }

        // Initialize aggregator if not set
        if (!isset($aggregated[$valueRaw])) {
            $aggregated[$valueRaw] = [
                'Value'          => $valueRaw, // to display
                'QTY Purchased'  => 0,
                'QTY Used'       => 0,
                'Stamps On Hand' => 0,
                'Total Purchased'=> 0.0,
                'Cost of Postage'=> 0.0,
                'NumericValue'   => $stampNumericValue // store for calculations
            ];
        }

        // Accumulate
        $aggregated[$valueRaw]['QTY Purchased'] += $qtyPurchased;
        $aggregated[$valueRaw]['QTY Used']      += $qtyUsed;
    }

    // After summing QTYs, compute 'Stamps On Hand', 'Total Purchased', 'Cost of Postage'
    foreach ($aggregated as $valueKey => &$stamp) {
        $purchased = $stamp['QTY Purchased'];
        $used      = $stamp['QTY Used'];
        $numeric   = $stamp['NumericValue']; // e.g. 0.63 for Forever

        $stamp['Stamps On Hand'] = $purchased - $used;
        // total purchased = purchased * numeric
        $stamp['Total Purchased'] = round($purchased * $numeric, 2);
        // cost of postage = used * numeric
        $stamp['Cost of Postage'] = round($used * $numeric, 2);
    }
    unset($stamp);

    // Convert from assoc to indexed array
    return array_values($aggregated);
}



/**
 * Fetch all rows from tbl_Expense, optionally with date filtering.
 */
function fetchAllExpenses(PDO $pdo, string $tableName, ?string $startDate = null, ?string $endDate = null, ?string $dateColumn = null): array
{
    $query = "SELECT * FROM `$tableName`";
    $params = [];

    if ($dateColumn && $startDate && $endDate) {
        $query .= " WHERE `$dateColumn` BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate']   = $endDate;
    }

    $query .= " ORDER BY `ID` ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'query' => $query,
            'params' => $params
        ];
    } catch (PDOException $e) {
        error_log("Error retrieving expense data: " . $e->getMessage());
        throw new \RuntimeException("Error retrieving expense data.");
    }
}

function fetchData($pdo, $tableName, $columns = '*') {
    // Wrap each column in backticks if not already
    $escapedColumns = array_map(function ($col) {
        $col = trim($col);
        return (str_contains($col, '`')) ? $col : "`$col`";
    }, explode(',', $columns));

    $columnsString = implode(',', $escapedColumns);
    $query = "SELECT $columnsString FROM `$tableName`";

    try {
        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            error_log("No data found in table `$tableName` for columns $columns.");
        }

        return $data;
    } catch (PDOException $e) {
        die("Error fetching data from `$tableName`: " . $e->getMessage());
    }
}

function fetchData2(PDO $pdo, $tableName)
{
    $output = [];

    try {
        // Query every column from the specified table
        $stmt = $pdo->query("SELECT * FROM `$tableName`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If the table is empty or doesn't exist
        if (!$rows) {
            return [];
        }

        // Build CSV header from column names
        $headers = array_keys($rows[0]); // e.g. ['Call','CardsReceived','FirstName',...]
        $headerLine = '"' . implode('","', $headers) . '"';
        $output[] = $headerLine;

        // Build CSV lines for each row
        foreach ($rows as $row) {
            $lineParts = [];
            foreach ($headers as $colName) {
                $val = $row[$colName] ?? '';
                // Escape double quotes
                $val = str_replace('"', '""', $val);
                $lineParts[] = "\"$val\"";
            }
            $output[] = implode(',', $lineParts);
        }
    } catch (PDOException $e) {
        echo "Error: Could not retrieve data from $tableName. " . $e->getMessage() . "<br>";
        return [];
    }

    return $output;
}

/**
 * Fetch all rows from the specified table.
 */
function fetchData3(PDO $pdo, $tableName)
{
    $query = "SELECT * FROM `$tableName` ORDER BY `Call` ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error retrieving data: " . $e->getMessage());
    }
}
/**
 * Trims each header string
 */
function normalizeHeaders($headers)
{
    return array_map('trim', $headers);
}

/**
 * Removes #mailto:...# patterns in the email field
 */
function sanitizeEmail($email)
{
    return preg_replace('/#mailto:[^#]+#/', '', $email);
}

/**
 * Sends an email to the user regarding unpaid QSL cards.
 */
function sendEmail($to, $call, $cardsOnHand, $emailConfig)
{
    $mail = new PHPMailer(true);
    $debugEmail = $emailConfig['debug_email'];

    if ($emailConfig['testing']) {
        // Override actual recipient
        $to = $debugEmail;
        echo "Testing enabled: Email will be sent to debug address ($debugEmail) instead.<br>";
    }

    try {
        $mail->SMTPDebug = $emailConfig['debugging'] ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = $emailConfig['server'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['sender'];
        $mail->Password   = $emailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $emailConfig['port'];

        // Set email details
        $mail->setFrom($emailConfig['from'], $emailConfig['from_name']);
        $mail->addAddress($to);
        $mail->isHTML($emailConfig['send_html']);
        $mail->Subject = 'Incoming DX Card(s) Notification';

        // Email body
        $mail->Body = "
            <img src='cid:7thArea' alt='7th Area' /><br>
            Hello <b>$call</b>,<br><br>
            My name is {$emailConfig['myname']} {$emailConfig['mycall']}. I am the ARRL 7th district QSL sorter for the {$emailConfig['sections']}.<br>
            I am writing you today because you have incoming DX card(s) in the incoming QSL bureau. 
            Cards on hand: <b>$cardsOnHand</b>.<br>
            If you would like to receive these cards, please go to 
            <a href='https://wvdxc.org/pay-online-for-credits/'>pay online for credits</a> or use the mail-in form.<br>
            <span style='color:red; font-weight:bold;'>Please respond within 30 days</span>, or else your account will be marked discard all incoming bureau cards.<br><br>
            If you would NOT like to receive incoming bureau cards, please let me know.<br><br>
            If you have any questions or concerns, please reply to this email or email me at {$emailConfig['from']}.<br><br>
            You can read more about the 7th district QSL bureau at 
            <a href='https://wvdxc.org/qsl-bureau-faq'>QSL Bureau FAQ</a>.<br><br>
            Best regards,<br>
            {$emailConfig['myname']} {$emailConfig['mycall']}<br>
            ARRL 7th District QSL Sorter – {$emailConfig['sections']}<br>
            {$emailConfig['from']}
        ";



        // Embed the 7thArea.png image in the email
        $mail->addEmbeddedImage('../7thArea.png', '7thArea');

        // Headers
        $mail->addCustomHeader('Return-Receipt-To', $emailConfig['from']);
        $mail->addCustomHeader('Disposition-Notification-To', $emailConfig['from']);

        // Send
        $mail->send();
        echo "Message has been sent to $call ($to)<br>";
    } catch (Exception $e) {
        echo "Message could not be sent to $call ($to). Mailer Error: {$mail->ErrorInfo}<br>";
    }
}

?>