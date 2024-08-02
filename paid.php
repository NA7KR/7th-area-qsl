<?php
session_start();

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Users to pay Page";
$config = include('config.php');
include("$root/backend/header.php");

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');

function fetchData($dbPath, $tableName) {
    $command = "mdb-export '$dbPath' '$tableName'";
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    if ($return_var !== 0) {
        echo "Error: Could not retrieve data from $tableName.";
        return [];
    }
    return $output;
}

function normalizeHeaders($headers) {
    return array_map('trim', $headers);
}

function sanitizeEmail($email) {
    return preg_replace('/#mailto:[^#]+#/', '', $email);
}

$selectedLetter = null;
$filterEmail = false;
$filterNoEmail = false;
$cardData = [];
$mailedData = [];
$returnedData = [];
$moneyReceivedData = [];
$postalCostData = [];
$otherCostData = [];
$operatorData = [];
$totalCardsReceived = 0;
$totalCardsMailed = 0;
$totalCardsReturned = 0;
$totalCardsOnHand = 0;
$totalPaid = 0;
$totalMoneyReceived = 0;
$totalPostalCost = 0;
$totalOtherCost = 0;
$totalTotal = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    $filterEmail = isset($_POST['filter_email']);
    $filterNoEmail = isset($_POST['filter_no_email']);
    if (isset($config['sections'][$selectedLetter])) {
        $dbPath = $config['sections'][$selectedLetter];

        // Fetch Card Data
        $rawCardData = fetchData($dbPath, 'tbl_CardRec');
        if (!empty($rawCardData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawCardData)));
            $callIndex = array_search('Call', $headers);
            $cardsReceivedIndex = array_search('CardsReceived', $headers);
            foreach ($rawCardData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $cardsReceivedIndex !== false) {
                    $call = $columns[$callIndex];
                    $cardsReceived = (int)$columns[$cardsReceivedIndex];
                    if (isset($cardData[$call])) {
                        $cardData[$call] += $cardsReceived;
                    } else {
                        $cardData[$call] = $cardsReceived;
                    }
                    $totalCardsReceived += $cardsReceived;
                }
            }
        }

        // Fetch Mailed Data
        $rawMailedData = fetchData($dbPath, 'tbl_CardM');
        if (!empty($rawMailedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawMailedData)));
            $callIndex = array_search('Call', $headers);
            $cardsMailedIndex = array_search('CardsMailed', $headers);
            $postalCostIndex = array_search('Postal Cost', $headers);
            $otherCostIndex = array_search('Other Cost', $headers);
            foreach ($rawMailedData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $cardsMailedIndex !== false) {
                    $call = $columns[$callIndex];
                    $cardsMailed = (int)$columns[$cardsMailedIndex];
                    $postalCost = isset($columns[$postalCostIndex]) ? (float)trim($columns[$postalCostIndex]) : 0.0;
                    $otherCost = isset($columns[$otherCostIndex]) ? (float)trim($columns[$otherCostIndex]) : 0.0;
                    if (isset($mailedData[$call])) {
                        $mailedData[$call] += $cardsMailed;
                    } else {
                        $mailedData[$call] = $cardsMailed;
                    }
                    if (isset($postalCostData[$call])) {
                        $postalCostData[$call] += $postalCost;
                    } else {
                        $postalCostData[$call] = $postalCost;
                    }
                    if (isset($otherCostData[$call])) {
                        $otherCostData[$call] += $otherCost;
                    } else {
                        $otherCostData[$call] = $otherCost;
                    }
                    $totalCardsMailed += $cardsMailed;
                }
            }
        }

        // Fetch Returned Data
        $rawReturnedData = fetchData($dbPath, 'tbl_CardRet');
        if (!empty($rawReturnedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawReturnedData)));
            $callIndex = array_search('Call', $headers);
            $cardsReturnedIndex = array_search('CardsReturned', $headers);
            foreach ($rawReturnedData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $cardsReturnedIndex !== false) {
                    $call = $columns[$callIndex];
                    $cardsReturned = (int)$columns[$cardsReturnedIndex];
                    if (isset($returnedData[$call])) {
                        $returnedData[$call] += $cardsReturned;
                    } else {
                        $returnedData[$call] = $cardsReturned;
                    }
                    $totalCardsReturned += $cardsReturned;
                }
            }
        }

        // Fetch Money Received Data
        $rawMoneyReceivedData = fetchData($dbPath, 'tbl_MoneyR');
        if (!empty($rawMoneyReceivedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawMoneyReceivedData)));
            $callIndex = array_search('Call', $headers);
            $moneyReceivedIndex = array_search('MoneyReceived', $headers);
            foreach ($rawMoneyReceivedData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $moneyReceivedIndex !== false) {
                    $call = $columns[$callIndex];
                    $moneyReceived = (float)trim($columns[$moneyReceivedIndex]);
                    if (isset($moneyReceivedData[$call])) {
                        $moneyReceivedData[$call] += $moneyReceived;
                    } else {
                        $moneyReceivedData[$call] = $moneyReceived;
                    }
                }
            }
            foreach ($moneyReceivedData as $call => $moneyReceived) {
                $totalPaid += $moneyReceived;
            }
        }

        // Fetch Operator Data
        $rawOperatorData = fetchData($dbPath, 'tbl_Operator');
        if (!empty($rawOperatorData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawOperatorData)));
            $callIndex = array_search('Call', $headers);
            $firstNameIndex = array_search('FirstName', $headers);
            $lastNameIndex = array_search('LastName', $headers);
            $mailInstIndex = array_search('Mail-Inst', $headers);
            $emailIndex = array_search('E-Mail', $headers);
            $addressIndex = array_search('Address_1', $headers);
            $cityIndex = array_search('City', $headers);
            $stateIndex = array_search('State', $headers);
            $zipIndex = array_search('Zip', $headers);
            foreach ($rawOperatorData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $mailInstIndex !== false && $emailIndex !== false) {
                    $call = $columns[$callIndex];
                    $firstName = isset($columns[$firstNameIndex]) ? $columns[$firstNameIndex] : '';
                    $lastName = isset($columns[$lastNameIndex]) ? $columns[$lastNameIndex] : '';
                    $mailInst = strtolower(trim($columns[$mailInstIndex] ?? ''));
                    $email = sanitizeEmail(trim($columns[$emailIndex] ?? ''));
                    $address = isset($columns[$addressIndex]) ? $columns[$addressIndex] : '';
                    $city = isset($columns[$cityIndex]) ? $columns[$cityIndex] : '';
                    $state = isset($columns[$stateIndex]) ? $columns[$stateIndex] : '';
                    $zip = isset($columns[$zipIndex]) ? $columns[$zipIndex] : '';
                    $operatorData[$call] = [
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'mailInst' => $mailInst,
                        'email' => $email,
                        'address' => $address,
                        'city' => $city,
                        'state' => $state,
                        'zip' => $zip,
                        'moneyReceived' => $moneyReceivedData[$call] ?? 0,
                        'postalCost' => $postalCostData[$call] ?? 0,
                        'otherCost' => $otherCostData[$call] ?? 0
                    ];
                }
            }
        } else {
            echo "Error: tbl_Operator data is empty or could not be fetched.";
        }

        // Process Data
        $redData = [];
        foreach ($operatorData as $call => $data) {
            $cardsReceived = $cardData[$call] ?? 0;
            $cardsMailed = $mailedData[$call] ?? 0;
            $cardsReturned = $returnedData[$call] ?? 0;
            $cardsOnHand = $cardsReceived - $cardsMailed - $cardsReturned;
            $totalCardsOnHand += $cardsOnHand;
            $totalCost = $data['postalCost'] + $data['otherCost'];
            $total = $data['moneyReceived'] - $totalCost;

            if ($data['moneyReceived'] > 0 && ((!$filterEmail && !$filterNoEmail) || 
                ($filterEmail && !empty($data['email'])) || 
                ($filterNoEmail && empty($data['email'])))) {
                $totalMoneyReceived += $data['moneyReceived'];
                $totalPostalCost += $data['postalCost'];
                $totalOtherCost += $data['otherCost'];
                $totalTotal += $total;
                $entry = [
                    'Call' => $call,
                    'FirstName' => $data['firstName'],
                    'LastName' => $data['lastName'],
                    'CardsOnHand' => $cardsOnHand,
                    'MoneyReceived' => $data['moneyReceived'],
                    'TotalCost' => $totalCost,
                    'Total' => $total,
                    'MailInst' => $data['mailInst'],
                    'Email' => $data['email'],
                    'Address' => $data['address'],
                    'City' => $data['city'],
                    'State' => $data['state'],
                    'Zip' => $data['zip']
                ];
                $redData[] = $entry;
            }
        }
        usort($redData, function($a, $b) {
            return strcasecmp($a['Call'], $b['Call']);
        });
    } else {
        echo "Error: Invalid database configuration.";
    }
}
?>

<div class="center-content">
    <style>
        /* Scoped form styles */
        .center-content form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .center-content form label {
            margin-bottom: 10px;
        }
        .center-content form input,
        .center-content form select,
        .center-content form button {
            margin-bottom: 20px;
        }
    </style>

    <img src="7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>
    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php if (isset($config['sections']) && is_array($config['sections'])): ?>
                <?php foreach ($config['sections'] as $letter => $dbPath): ?>
                    <option value="<?= htmlspecialchars($letter) ?>" <?= $selectedLetter === $letter ? 'selected' : '' ?>>
                        <?= htmlspecialchars($letter) ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">No sections available</option>
            <?php endif; ?>
        </select>
        <br><br>
       
        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($redData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Cards on Hand: <?= $totalCardsOnHand ?></p>
        <p>Total Paid: <?= $totalPaid ?></p>
        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Cards On Hand</th>
                    <th>Money Received</th>
                    <th>Shipping Cost</th>
                    <th>Total Available</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Zip</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($redData as $row): ?>
                    <tr>
                        <td class="call red"><?= htmlspecialchars($row['Call']) ?></td>
                        <td class="first-name"><?= htmlspecialchars($row['FirstName']) ?></td>
                        <td class="last-name"><?= htmlspecialchars($row['LastName']) ?></td>
                        <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                        <td><?= htmlspecialchars($row['MoneyReceived']) ?></td>
                        <td><?= htmlspecialchars($row['TotalCost']) ?></td>
                        <td><?= htmlspecialchars($row['Total']) ?></td>
                        <td><?= htmlspecialchars($row['Email']) ?></td>
                        <td class="address"><?= htmlspecialchars($row['Address']) ?></td>
                        <td class="city"><?= htmlspecialchars($row['City']) ?></td>
                        <td class="state"><?= htmlspecialchars($row['State']) ?></td>
                        <td class="zip"><?= htmlspecialchars($row['Zip']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4"><strong>Totals</strong></td>
                    <td><strong><?= htmlspecialchars($totalMoneyReceived) ?></strong></td>
                    <td><strong><?= htmlspecialchars($totalPostalCost + $totalOtherCost) ?></strong></td>
                    <td><strong><?= htmlspecialchars($totalTotal) ?></strong></td>
                    <td colspan="5"></td>
                </tr>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>

<?php
include("$root/backend/footer.php");
?>
