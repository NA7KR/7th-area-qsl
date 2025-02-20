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
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
include_once("$root/backend/PHPMailer.php");

  
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
    $root = realpath($_SERVER["DOCUMENT_ROOT"]);
    include_once("$root/backend/PHPMailer.php");
    require_once "$root/backend/SMTP.php";
    require_once "$root/backend/Exception.php";
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
            ARRL 7th District QSL Sorter {$emailConfig['sections']}<br>
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
    $csvMailedData = fetchData(
        $pdo,
        'tbl_CardM',
        ['Call', 'CardsMailed', 'DateMailed'], // Or "Call,CardsMailed,DateMailed" or omit for all columns "*"
        $startDate, // Your start date (or null if not filtering)
        $endDate,   // Your end date (or null if not filtering)
        $enableDateFilter, // Whether to use date filtering
        true // Return CSV
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

/**
 * Fetches data from MySQL using PDO, offering CSV or array output.
 *
 * @param PDO $pdo PDO connection to the MySQL database
 * @param string $tableName The table from which to retrieve data
 * @param string|array $columns Columns to select (string like "Call,CardsMailed,DateMailed" or array)
 * @param string|null $startDate Optional start date (YYYY-MM-DD) for filtering
 * @param string|null $endDate Optional end date (YYYY-MM-DD) for filtering
 * @param bool $enableDateFilter Whether to apply the date range filtering
 * @param bool $returnCSV If true, returns CSV-like output; otherwise, returns an array of associative arrays
 * @param string $orderBy Optional ORDER BY clause
 * @param string $whereClause Optional WHERE clause
 *
 * @return string[]|array Array of CSV lines or array of associative arrays
 */
function fetchData(
    PDO $pdo,
    string $tableName,
    string|array $columns = "*",
    ?string $startDate = null,
    ?string $endDate = null,
    bool $enableDateFilter = false,
    bool $returnCSV = false,
    string $orderBy = "",
    string $whereClause = ""
): array|string {

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

    if (!empty($whereClause)) {
        $conditions[] = $whereClause;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    if (!empty($orderBy)) {
        $query .= " " . $orderBy;
    }
    //echo "Query:  $query<br>";

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

    if ($returnCSV) {
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
    } else {
        return $rows;
    }
}

function fetchJoinedData(PDO $pdo, string $tableName1, string $tableName2, ?string $startDate = null, ?string $endDate = null): array
{
    $query = "SELECT tbl_CardRet.Call, SUM(tbl_CardRet.CardsReturned) AS TotalCardsReturned, MAX(tbl_Operator.`Status`) AS Status
              FROM `$tableName1`
              INNER JOIN `$tableName2` ON tbl_CardRet.Call = tbl_Operator.Call
              WHERE tbl_CardRet.Call IS NOT NULL AND tbl_Operator.Call IS NOT NULL";

    $params = [];

    if ($startDate && $endDate) {
        $query .= " AND tbl_CardRet.DateReturned BETWEEN :startDate AND :endDate";
        $params[':startDate'] = $startDate;
        $params[':endDate'] = $endDate;
    }

    $query .= " GROUP BY tbl_CardRet.Call ORDER BY tbl_CardRet.Call ASC";

    //echo $query; // Debugging: Output the constructed query

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'query' => $query,
            'params' => $params
        ];
    } catch (PDOException $e) {
        error_log("Error retrieving data: " . $e->getMessage());
        throw new \RuntimeException("Error retrieving data.");
    }
}

/**
 * Fetch mailed data for a single call.
 * 
 * @param PDO $pdo The PDO connection object
 * @param string $callSign The call sign to fetch data for
 * @return array The fetched data as an associative array
 */
function fetchMailedData(PDO $pdo, string $callSign): array
{
    // SQL query to fetch mailed data for the given call sign
    $sql = "SELECT `Call`, `CardsMailed`, `Postal Cost`, `Other Cost`
            FROM `tbl_CardM`
            WHERE `Call` = :call";

    // Log the SQL query and parameter for debugging
    error_log("[DEBUG] SQL => $sql, param => $callSign");

    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':call', $callSign, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch and return the result as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch money received data for a single call.
 * 
 * @param PDO $pdo The PDO connection object
 * @param string $callSign The call sign to fetch data for
 * @return array The fetched data as an associative array
 */
function fetchMoneyReceived(PDO $pdo, string $callSign): array
{
    // SQL query to fetch money received data for the given call sign
    $sql = "SELECT `Call`, `MoneyReceived`
            FROM `tbl_MoneyR`
            WHERE `Call` = :call";

    // Log the SQL query and parameter for debugging
    error_log("[DEBUG] SQL => $sql, param => $callSign");

    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':call', $callSign, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch and return the result as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

function insertData($conn, $callsign, $first_name, $last_name, $class, $date_start, $date_exp, $new_call, $old_call, $address, $address2, $city, $state, $zip, $country, $email, $phone, $born, $custom, $role) {

    $stmt = $conn->prepare("INSERT INTO tbl_Operator (Call_Index, FirstName, LastName, Class, Lic_issued, Lic_exp, NewCall, Old_call, Address_1, Address_2, City, State, Zip, Country, E_Mail, Phone, DOB, Custom_Field, Role) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); // Added Country, Custom_Field, Role

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssssssssssssssssssss", $callsign, $first_name, $last_name, $class, $date_start, $date_exp, $new_call, $old_call, $address, $address2, $city, $state, $zip, $country, $email, $phone, $born, $custom, $role); // Added parameters for Country, Custom_Field, and Role

    if ($stmt->execute()) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

function getFirstLetterAfterNumber($call) {
    if (preg_match('/(\d+)([a-zA-Z])/', $call, $matches)) {
        return $matches[2]; // The letter is in the second capturing group
    } elseif (preg_match('/^([a-zA-Z])/', $call, $matches)) { // Check for a letter at the beginning if no number
        return $matches[1];
    } else {
        return "Error: Invalid format. Input: $call";
    }
}


/**
 * Inserts a new user and a corresponding section record into the database.
 *
 * @param string $callsign     The username/callsign.
 * @param string $role         The user role.
 * @param string $email        The user email.
 * @param string $letter       The section letter.
 * @param string $sectionStatus The section status.
 *
 * @return string A message indicating success or describing the error.
 */
function upsertUserAndSection($callsign, $role = null, $email = null, $letter = null, $sectionStatus = null, $delete = false, $fetchOnly = false) {
    try {
        // Get the PDO connection
        $config = include('../config.php');
        $dbInfo = $config['db'];
        $pdo = getPDOConnection($dbInfo);

        if ($delete) {
            // Delete user and section
            $deleteUserSql = "DELETE FROM `users` WHERE `username` = :call";
            $deleteUserStmt = $pdo->prepare($deleteUserSql);
            $deleteUserStmt->bindValue(':call', $callsign, PDO::PARAM_STR);
            $deleteUserStmt->execute();

            $deleteSectionSql = "DELETE FROM `sections` WHERE `call` = :call";
            $deleteSectionStmt = $pdo->prepare($deleteSectionSql);
            $deleteSectionStmt->bindValue(':call', $callsign, PDO::PARAM_STR);
            $deleteSectionStmt->execute();

            return ["message" => "User and section deleted successfully"];
        }

        // Fetch user and section data without modifying
        if ($fetchOnly) {
            $fetchSql = "
                SELECT 
                    u.username, u.role, u.email, 
                    s.letter, s.status 
                FROM users u 
                LEFT JOIN sections s ON u.username = s.call 
                WHERE u.username = :call
            ";
            $fetchStmt = $pdo->prepare($fetchSql);
            $fetchStmt->bindValue(':call', $callsign, PDO::PARAM_STR);
            $fetchStmt->execute();
            $result = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result : ["message" => "No data found for callsign: $callsign"];
        }

        // Proceed with Upsert logic (Same as before)
        $pdo->beginTransaction();

        $checkSql = "SELECT id FROM users WHERE `username` = :call";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindValue(':call', $callsign, PDO::PARAM_STR);
        $checkStmt->execute();
        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $sql1 = "UPDATE `users` SET `role` = :role, `email` = :email WHERE `username` = :call";
        } else {
            $sql1 = "INSERT INTO `users` (`username`, `role`, `email`) VALUES (:call, :role, :email)";
        }
        
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->bindValue(':call', $callsign, PDO::PARAM_STR);
        $stmt1->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt1->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt1->execute();

        // Upsert section
        $checkSectionSql = "SELECT id FROM sections WHERE `call` = :call";
        $checkSectionStmt = $pdo->prepare($checkSectionSql);
        $checkSectionStmt->bindValue(':call', $callsign, PDO::PARAM_STR);
        $checkSectionStmt->execute();
        $section = $checkSectionStmt->fetch(PDO::FETCH_ASSOC);

        if ($section) {
            $sql2 = "UPDATE `sections` SET `letter` = :letter, `status` = :status WHERE `call` = :call";
        } else {
            $sql2 = "INSERT INTO `sections` (`letter`, `call`, `status`) VALUES (:letter, :call, :status)";
        }

        $stmt2 = $pdo->prepare($sql2);
        $stmt2->bindValue(':letter', $letter, PDO::PARAM_STR);
        $stmt2->bindValue(':call', $callsign, PDO::PARAM_STR);
        $stmt2->bindValue(':status', $sectionStatus, PDO::PARAM_STR);
        $stmt2->execute();

        $pdo->commit();

        // Fetch and return updated data
        return upsertUserAndSection($callsign, null, null, null, null, false, true);

    } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => "Database error: " . $e->getMessage()];
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ["error" => "An error occurred: " . $e->getMessage()];
    }
}



function fetchFilteredData($pdo, $netBalanceMin = 0.88, $statusFilter = ['Active', 'License Expired'], $cardsOnHandMin = 0) {
    // Dynamically create a parameterized IN clause for statuses
    $statusPlaceholders = implode(',', array_fill(0, count($statusFilter), '?'));

    $query = "
        SELECT 
            o.`Call`,
            o.`FirstName`,
            o.`LastName`,
            o.`Mail-Inst`,
            o.`E-Mail` AS Email,
            o.`Address_1` AS Address,
            o.`City`,
            o.`State`,
            o.`Zip`,
            o.`Status`,
            o.`Lic-exp`,
            o.`Remarks`,
            COALESCE(cr.CardsReceived, 0) AS CardsReceived,
            COALESCE(cm.CardsMailed, 0) AS CardsMailed,
            COALESCE(crt.CardsReturned, 0) AS CardsReturned,
            COALESCE(cm.TotalCost, 0) AS TotalCost,
            COALESCE(mr.MoneyReceived, 0) AS MoneyReceived,
            (COALESCE(cr.CardsReceived, 0) - COALESCE(cm.CardsMailed, 0) - COALESCE(crt.CardsReturned, 0)) AS CardsOnHand,
            (COALESCE(mr.MoneyReceived, 0) - COALESCE(cm.TotalCost, 0)) AS NetBalance
        FROM tbl_Operator o
        LEFT JOIN (
            SELECT `Call`, SUM(`CardsReceived`) AS CardsReceived
            FROM tbl_CardRec
            GROUP BY `Call`
        ) cr ON o.`Call` = cr.`Call`
        LEFT JOIN (
            SELECT `Call`, SUM(`CardsMailed`) AS CardsMailed, SUM(`Total Cost`) AS TotalCost
            FROM tbl_CardM
            GROUP BY `Call`
        ) cm ON o.`Call` = cm.`Call`
        LEFT JOIN (
            SELECT `Call`, SUM(`CardsReturned`) AS CardsReturned
            FROM tbl_CardRet
            GROUP BY `Call`
        ) crt ON o.`Call` = crt.`Call`
        LEFT JOIN (
            SELECT `Call`, SUM(`MoneyReceived`) AS MoneyReceived
            FROM tbl_MoneyR
            GROUP BY `Call`
        ) mr ON o.`Call` = mr.`Call`
        WHERE o.`Status` IN ($statusPlaceholders)
    ";

    // Modify HAVING clause dynamically based on cardsOnHandMin
    $havingClause = " HAVING NetBalance < ?";
    if ($cardsOnHandMin !== null) {
        $havingClause .= " AND CardsOnHand > ?";
    }
    
    $query .= $havingClause;

    $stmt = $pdo->prepare($query);

    // Bind dynamic status values
    foreach ($statusFilter as $key => $status) {
        $stmt->bindValue($key + 1, $status, PDO::PARAM_STR);
    }
    
    // Bind NetBalance threshold
    $stmt->bindValue(count($statusFilter) + 1, $netBalanceMin, PDO::PARAM_STR);

    // Bind CardsOnHand threshold if it's set
    if ($cardsOnHandMin !== null) {
        $stmt->bindValue(count($statusFilter) + 2, $cardsOnHandMin, PDO::PARAM_INT);
    }

    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getTotalCardsOnHand($pdo) {
    $sql = "
        SELECT 
            SUM(COALESCE(cr.CardsReceived, 0) - COALESCE(cm.CardsMailed, 0) - COALESCE(crt.CardsReturned, 0)) AS TotalCardsOnHand
        FROM tbl_Operator o
        LEFT JOIN (
            SELECT `Call`, SUM(`CardsReceived`) AS CardsReceived
            FROM tbl_CardRec
            GROUP BY `Call`
        ) cr ON o.`Call` = cr.`Call`
        LEFT JOIN (
            SELECT `Call`, SUM(`CardsMailed`) AS CardsMailed, SUM(`Total Cost`) AS TotalCost
            FROM tbl_CardM
            GROUP BY `Call`
        ) cm ON o.`Call` = cm.`Call`
        LEFT JOIN (
            SELECT `Call`, SUM(`CardsReturned`) AS CardsReturned
            FROM tbl_CardRet
            GROUP BY `Call`
        ) crt ON o.`Call` = crt.`Call`
        LEFT JOIN (
            SELECT `Call`, SUM(`MoneyReceived`) AS MoneyReceived
            FROM tbl_MoneyR
            GROUP BY `Call`
        ) mr ON o.`Call` = mr.`Call`
        WHERE o.`Status` IN ('Active')
        HAVING TotalCardsOnHand > 0;
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['TotalCardsOnHand'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error fetching total cards on hand: " . $e->getMessage());
        return 0;
    }
}

?>