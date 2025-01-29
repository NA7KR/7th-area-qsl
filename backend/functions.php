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

/**
 * Parses CSV-like data for "Call" and "CardsMailed" columns, returning an array plus total cards mailed.
 * 
 * @param string[] $rawData CSV lines (the first line is the header, subsequent lines are data)
 *
 * @return array [ (array $mailedData), (int $totalCardsMailed) ]
 */
function parseMailedData(array $rawData): array
{
    if (empty($rawData)) {
        return [[], 0];
    }

    // First line is the CSV header
    $headers           = str_getcsv(array_shift($rawData), ",", "\"", "\\");
    $callIndex         = array_search('Call',        $headers);
    $cardsMailedIndex  = array_search('CardsMailed', $headers);

    if ($callIndex === false || $cardsMailedIndex === false) {
        echo "Error: Could not find the required columns in the data.";
        return [[], 0];
    }

    $mailedData       = [];
    $totalCardsMailed = 0;

    foreach ($rawData as $line) {
        $columns = str_getcsv($line, ",", "\"", "\\");
        if (isset($columns[$callIndex]) && isset($columns[$cardsMailedIndex])) {
            $call              = $columns[$callIndex];
            $cardsMailed       = (int) $columns[$cardsMailedIndex];
            $mailedData[]      = [
                'Call'        => $call,
                'CardsMailed' => $cardsMailed,
            ];
            $totalCardsMailed += $cardsMailed;
        }
    }

    // Sort by Call sign
    usort($mailedData, fn($a, $b) => strcasecmp($a['Call'], $b['Call']));

    return [$mailedData, $totalCardsMailed];
}

/**
 * Handles form submission, opens a PDO connection, fetches & parses data, and returns an array with results.
 *
 * @param array $config    Configuration array including 'sections' with DB credentials
 * @param bool  $debugMode Whether to echo the SQL query for debugging
 *
 * @return array [ (string|null $selectedLetter), (array $parsedData) ]
 */
function handleFormSubmission(array $config, bool $debugMode): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return [null, [[], 0]];
    }

    $selectedLetter   = $_POST['letter']            ?? null;
    $enableDateFilter = isset($_POST['dateFilterCheckbox']);
    $startDate        = $_POST['startDate']         ?? null; // e.g., "2025-01-01"
    $endDate          = $_POST['endDate']           ?? null; // e.g., "2025-01-30"

    if (!$selectedLetter || !isset($config['sections'][$selectedLetter])) {
        echo "Error: Invalid database configuration.";
        return [null, [[], 0]];
    }

    // Build PDO connection from config
    $dbInfo = $config['sections'][$selectedLetter];

    try {
        $dsn = "mysql:host={$dbInfo['host']};dbname={$dbInfo['dbname']};charset=utf8";
        $pdo = new PDO($dsn, $dbInfo['username'], $dbInfo['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Fetch from tbl_CardM
    $csvMailedData = fetchDataCombined(
        $pdo,
        'tbl_CardM',
        ['Call', 'CardsMailed', 'DateMailed'], // Or "Call,CardsMailed,DateMailed" or omit for all columns "*"
        $startDate, // Your start date (or null if not filtering)
        $endDate,   // Your end date (or null if not filtering)
        $enableDateFilter // Whether to use date filtering
    );
    // Parse into array & total
    return [$selectedLetter, parseMailedData($csvMailedData)];
}
function getCallTotals(PDO $pdo, string $tableName, array $keyIndexes): array
{
    $rawData = fetchTableData($pdo, $tableName, [$keyIndexes['keyName'], $keyIndexes['valueName']]);
    return !empty($rawData)
    ? accumulateCallData($rawData, $keyIndexes)
    : [];

}
/**
 * Fetches data from a given table with optional conditions and parameters.
 *
 * @param PDO $pdo
 * @param string $tableName
 * @param array $columns      Array of column names to select (defaults to all: ['*']).
 * @param string|null $conditions  SQL WHERE clause conditions (e.g., "Call = :call").
 * @param array $params       Parameters for the prepared statement (e.g., [':call' => 'W7XYZ']).
 * @return array             An array of associative arrays representing the fetched rows.
 */
function fetchTableData(PDO $pdo, string $tableName, array $columns = ['*'], ?string $conditions = null, array $params = []): array {  // ?string for nullable
    $columnsList = implode(", ", array_map(fn($col) => "`$col`", $columns));
    $query = "SELECT $columnsList FROM `$tableName`";

    if ($conditions !== null) { // Explicitly check for null
        $query .= " WHERE $conditions";
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching data from $tableName: " . $e->getMessage());
        return [];
    }
}


/**
 * Aggregates numeric values by call sign.
 *
 * @param array $rawData    An array of associative arrays, where each array represents a row from the database.
 * @param array $keyIndexes  An associative array containing the keys for the 'call sign' and 'value' columns:
 *                           ['keyName' => 'Call', 'valueName' => 'CardsReceived'] (Example)
 * @return array            An associative array where the keys are the call signs and the values are the 
 *                           summed numeric values.
 */
function accumulateCallData(array $rawData, array $keyIndexes): array {
    $aggregated = [];
    foreach ($rawData as $row) {
        $callSign = $row[$keyIndexes['keyName']];
        $value = (float)$row[$keyIndexes['valueName']]; // Cast to float for numeric operations

        // Use the null coalescing operator (??) for cleaner aggregation:
        $aggregated[$callSign] = ($aggregated[$callSign] ?? 0) + $value;
    }
    return $aggregated;
}

function fetchDataNEW(PDO $pdo, string $tableName, string $columns = "*", string $orderBy = "", string $whereClause = "", ?string $startDate = null, ?string $endDate = null, bool $enableDateFilter = false): array {
    try {
        $sql = "SELECT ";

        if ($columns === "*") {
            $sql .= "*";
        } else {
            $columns = trim($columns);

            if (empty($columns)) {
                $sql .= "*";
            } else {
                $escapedColumns = array_map(function ($col) {
                    $col = trim($col);
                    return (str_contains($col, '`')) ? $col : "`$col`";
                }, explode(',', $columns));

                $columnsString = implode(',', $escapedColumns);
                $sql .= $columnsString;
            }
        }

        $sql .= " FROM `" . $tableName . "`";

        // Date Filtering
        if ($enableDateFilter && $startDate !== null && $endDate !== null) {
            $dateColumn = "`DateColumn`"; // REPLACE with your actual date column name!
            $sql .= " WHERE " . $dateColumn . " BETWEEN :startDate AND :endDate";

            if (!empty($whereClause)) {
                $sql .= " AND " . $whereClause;
            }
        } else if (!empty($whereClause)) {
            $sql .= " WHERE " . $whereClause;
        }


        if (!empty($orderBy)) {
            $sql .= " " . $orderBy;
        }
        
        $stmt = $pdo->prepare($sql);

        if ($enableDateFilter && $startDate !== null && $endDate !== null) {
            $stmt->bindValue(':startDate', $startDate);
            $stmt->bindValue(':endDate', $endDate);
        }

        $stmt->execute();

        if ($stmt === false) {
            $errorInfo = $pdo->errorInfo();
            error_log("PDO Error: " . $errorInfo[2]);
            throw new Exception("Error executing query: " . $errorInfo[2]);
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data) && $columns !== "*") {
            error_log("No data found in table `$tableName` for columns $columns.");
        }

        return $data;

    } catch (Exception $e) {
        error_log($e->getMessage());
        return []; // Always return an array
    }
}


/**
 * Fetches data from MySQL using PDO, returning CSV-like lines.
 *
 *
 * @param PDO $pdo PDO connection to the MySQL database
 * @param string $tableName The table from which to retrieve data
 * @param string|array $columns Columns to select (string like "Call,CardsMailed,DateMailed" or array)
 * @param string|null $startDate Optional start date (YYYY-MM-DD) for filtering
 * @param string|null $endDate Optional end date (YYYY-MM-DD) for filtering
 * @param bool $enableDateFilter Whether to apply the date range filtering
 *
 * @return string[] Array of CSV lines (the first line is a header, subsequent lines are data)
 */
function fetchDataCombined(
    PDO $pdo,
    string $tableName,
    string|array $columns = "*",
    ?string $startDate = null,
    ?string $endDate = null,
    bool $enableDateFilter = false
): array {

    $query = "SELECT ";

    if ($columns === "*") {
        $query .= "*";
    } else {
        if (is_array($columns)) {
            $escapedColumns = array_map(function ($col) {
                $col = trim($col);
                return (str_contains($col, '`')) ? $col : "`$col`";
            }, $columns);
            $columnsString = implode(',', $escapedColumns);

            if (empty($columnsString)) { // Check if the columns string is empty
                $query .= "*"; // Select all if no columns specified
            } else {
                $query .= $columnsString;
            }
        } else { // Assume string
            $columns = trim($columns);
            if (empty($columns)) {
                $query .= "*";
            } else {
                $query .= $columns;
            }
        }
    }

    $query .= " FROM `$tableName`";

    $conditions = [];

    if ($enableDateFilter && $startDate && $endDate) {
        $conditions[] = "`DateMailed` BETWEEN :startDate AND :endDate"; // Use your actual date column name
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }
   
    try {
        $stmt = $pdo->prepare($query);

        if ($enableDateFilter && $startDate && $endDate) {
            $stmt->bindValue(':startDate', $startDate);
            $stmt->bindValue(':endDate', $endDate);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }

    $csvLines = [];
    if (!empty($rows)) { // Check if rows exist before trying to access keys.
        $headers = array_keys($rows[0]);
        $csvLines[] = '"' . implode('","', $headers) . '"';

        foreach ($rows as $row) {
            $lineParts = [];
            foreach ($headers as $colName) {
                $val = $row[$colName] ?? '';
                $val = str_replace('"', '""', $val); // Escape double quotes
                $lineParts[] = "\"$val\"";
            }
            $csvLines[] = implode(',', $lineParts);
        }
    }

    return $csvLines;
}
?>