www/7th-area-qsl/topay.php<?php
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

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Users to pay Page ";
$config = include('config.php');
include("$root/backend/header.php"); 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
$root = "/var/www/Working/";
include_once("$root/backend/Exception.php");
include_once("$root/backend/PHPMailer.php");
include_once("$root/backend/SMTP.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$config = include("config.php");
$emailConfig = $config['email'];

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

function sendEmail($to, $call, $cardsOnHand, $emailConfig) {
    $mail = new PHPMailer(true);
    $debugEmail = 'kevin@na7kr.us';
    if ($emailConfig['testing']) {
        $to = $debugEmail;
        echo "testing enabled <br>";
        echo $to . "<br>"; 
    }
   
    try {
        $mail->SMTPDebug = $emailConfig['debugging'] ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = $emailConfig['server'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['sender'];
        $mail->Password = $emailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $emailConfig['port'];
        $mail->setFrom('ars.kevin@na7kr.us', 'Kevin Roberts NA7KR');
        $mail->addAddress($to);
        $mail->isHTML($emailConfig['send_html']);
        $mail->Subject = 'Incoming DX Card(s) Notification';
        $mail->Body = "
            <img src='cid:7thArea' alt='7th Area' /><br>
            Hello $call,<br><br>
            My name is Kevin Roberts NA7KR. I am the ARRL 7th district QSL sorter for the F section.<br>
            I am writing you today because you have incoming DX card(s) in the incoming QSL bureau. Cards on hand: $cardsOnHand.<br>
            If you would like to receive these cards, please go to <a href='https://wvdxc.org/pay-online-for-credits/'>pay online for credits</a> or use the mail-in form.<br>
            Please respond within 30 days, or else your account will be marked ‘discard all incoming bureau cards’.<br><br>
            If you would NOT like to receive incoming bureau cards, please let me know.<br><br>
            If you have any questions or concerns, please reply to this email or email me at ARS.kevin@na7kr.us.<br><br>
            You can read more about the 7th district QSL bureau at <a href='https://wvdxc.org/qsl-bureau-faq'>QSL Bureau FAQ</a>.
        ";
        $mail->addEmbeddedImage('7thArea.png', '7thArea');

        $mail->send();
        echo "Message has been sent to $call ($to)<br>";
    } catch (Exception $e) {
        echo "Message could not be sent to $call ($to). Mailer Error: {$mail->ErrorInfo}<br>";
    }
}

$selectedLetter = null;
$filterEmail = false;
$filterNoEmail = false;
$cardData = [];
$mailedData = [];
$returnedData = [];
$moneyReceivedData = [];
$totalCostData = [];
$operatorData = [];
$totalCardsReceived = 0;
$totalCardsMailed = 0;
$totalCardsReturned = 0;
$totalCardsOnHand = 0;
$submittedData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    $filterEmail = isset($_POST['filter_email']);
    $filterNoEmail = isset($_POST['filter_no_email']);
    if (isset($config['sections'][$selectedLetter])) {
        $dbPath = $config['sections'][$selectedLetter];
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
        $rawMailedData = fetchData($dbPath, 'tbl_CardM');
        if (!empty($rawMailedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawMailedData)));
            $callIndex = array_search('Call', $headers);
            $cardsMailedIndex = array_search('CardsMailed', $headers);
            $totalCostIndex = array_search('Total Cost', $headers);
            foreach ($rawMailedData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $cardsMailedIndex !== false) {
                    $call = $columns[$callIndex];
                    $cardsMailed = (int)$columns[$cardsMailedIndex];
                    $totalCost = isset($columns[$totalCostIndex]) ? (float)$columns[$totalCostIndex] : 0.0;
                    if (isset($mailedData[$call])) {
                        $mailedData[$call] += $cardsMailed;
                    } else {
                        $mailedData[$call] = $cardsMailed;
                    }
                    if (isset($totalCostData[$call])) {
                        $totalCostData[$call] += $totalCost;
                    } else {
                        $totalCostData[$call] = $totalCost;
                    }
                    $totalCardsMailed += $cardsMailed;
                }
            }
        }
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
        $rawMoneyReceivedData = fetchData($dbPath, 'tbl_MoneyR');
        if (!empty($rawMoneyReceivedData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawMoneyReceivedData)));
            $callIndex = array_search('Call', $headers);
            $moneyReceivedIndex = array_search('MoneyReceived', $headers);
            foreach ($rawMoneyReceivedData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $moneyReceivedIndex !== false) {
                    $call = $columns[$callIndex];
                    $moneyReceived = (float)$columns[$moneyReceivedIndex];
                    if (isset($moneyReceivedData[$call])) {
                        $moneyReceivedData[$call] += $moneyReceived;
                    } else {
                        $moneyReceivedData[$call] = $moneyReceived;
                    }
                }
            }
        }
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
                        'zip' => $zip
                    ];
                }
            }
        } else {
            echo "Error: tbl_Operator data is empty or could not be fetched.";
        }
        $redData = [];
        foreach ($cardData as $call => $cardsReceived) {
            $cardsMailed = $mailedData[$call] ?? 0;
            $cardsReturned = $returnedData[$call] ?? 0;
            $moneyReceived = $moneyReceivedData[$call] ?? 0;
            $totalCost = $totalCostData[$call] ?? 0;
            $cardsOnHand = $cardsReceived - $cardsMailed - $cardsReturned;
            $totalCardsOnHand += $cardsOnHand;
            $firstName = $operatorData[$call]['firstName'] ?? '';
            $lastName = $operatorData[$call]['lastName'] ?? '';
            $mailInst = $operatorData[$call]['mailInst'] ?? 'full';
            $email = $operatorData[$call]['email'] ?? '';
            $address = $operatorData[$call]['address'] ?? '';
            $city = $operatorData[$call]['city'] ?? '';
            $state = $operatorData[$call]['state'] ?? '';
            $zip = $operatorData[$call]['zip'] ?? '';
            $total = $moneyReceived - $totalCost;
            if ($cardsOnHand > 0 && $total <= 0 && 
                ((!$filterEmail && !$filterNoEmail) || 
                ($filterEmail && !empty($email)) || 
                ($filterNoEmail && empty($email)))) {
                $entry = [
                    'Call' => $call,
                    'FirstName' => $firstName,
                    'LastName' => $lastName,
                    'CardsOnHand' => $cardsOnHand,
                    'MoneyReceived' => $moneyReceived,
                    'TotalCost' => $totalCost,
                    'Total' => $total,
                    'MailInst' => $mailInst,
                    'Email' => $email,
                    'Address' => $address,
                    'City' => $city,
                    'State' => $state,
                    'Zip' => $zip
                ];
                $redData[] = $entry;
            }
        }
        usort($redData, function($a, $b) {
            return strcasecmp($a['Call'], $b['Call']);
        });
        if (isset($_POST['selected_calls'])) {
            foreach ($_POST['selected_calls'] as $selectedCall) {
                foreach ($redData as $data) {
                    if ($data['Call'] === $selectedCall) {
                        $submittedData[] = $data;
                    }
                }
            }
            foreach ($submittedData as $row) {
                sendEmail($row['Email'], $row['Call'], $row['CardsOnHand'], $emailConfig);
            }
        }
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
        <label for="filter_email">
            <input type="checkbox" name="filter_email" id="filter_email" <?= $filterEmail ? 'checked' : '' ?> onclick="toggleCheckbox(this)">
            Show only users with email addresses
        </label>
        <br><br>
        <label for="filter_no_email">
            <input type="checkbox" name="filter_no_email" id="filter_no_email" <?= $filterNoEmail ? 'checked' : '' ?> onclick="toggleCheckbox(this)">
            Show only users without email addresses
        </label>
        <br><br>
        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($redData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Cards on Hand: <?= $totalCardsOnHand ?></p>
        <form method="POST">
            <input type="hidden" name="letter" value="<?= htmlspecialchars($selectedLetter) ?>">
            <input type="hidden" name="filter_email" value="<?= htmlspecialchars($filterEmail) ?>">
            <input type="hidden" name="filter_no_email" value="<?= htmlspecialchars($filterNoEmail) ?>">
            <h3>Red Table</h3>
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" onclick="toggleCheckbox(this)"> Select All</th>
                        <th>Call</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Cards On Hand</th>
                        <th>Money Received</th>
                        <th>Total Cost</th>
                        <th>Total</th>
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
                            <td>
                                <input type="checkbox" name="selected_calls[]" value="<?= htmlspecialchars($row['Call']) ?>">
                            </td>
                            <td class="call red">
                                <?= htmlspecialchars($row['Call']) ?>
                            </td>
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
                </tbody>
            </table>
            <button type="submit">Submit Selected</button>
        </form>
        <button type="button" onclick="printLabels()">Print Labels</button>
        <div id="labels"></div>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>

    <?php if (!empty($submittedData)): ?>
        <h2>Selected Data for Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>Cards On Hand</th>
                    <th>Money Received</th>
                    <th>Total Cost</th>
                    <th>Total</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Zip</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submittedData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Call']) ?></td>
                        <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                        <td><?= htmlspecialchars($row['MoneyReceived']) ?></td>
                        <td><?= htmlspecialchars($row['TotalCost']) ?></td>
                        <td><?= htmlspecialchars($row['Total']) ?></td>
                        <td><?= htmlspecialchars($row['Email']) ?></td>
                        <td><?= htmlspecialchars($row['Address']) ?></td>
                        <td><?= htmlspecialchars($row['City']) ?></td>
                        <td><?= htmlspecialchars($row['State']) ?></td>
                        <td><?= htmlspecialchars($row['Zip']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <form method="POST">
            <input type="hidden" name="letter" value="<?= htmlspecialchars($selectedLetter) ?>">
            <button type="submit">Back</button>
        </form>
        <h3>Email Templates</h3>
        <?php foreach ($submittedData as $row): ?>
            <pre>
Hello <?= htmlspecialchars($row['Call']) ?>,

My name is Kevin Roberts NA7KR. I am the ARRL 7th district QSL sorter for the F section.
I am writing you today because you have incoming DX card(s) in the incoming QSL bureau. Cards on hand: <?= htmlspecialchars($row['CardsOnHand']) ?>.
If you would like to receive these cards, please go to https://wvdxc.org/pay-online-for-credits/ and pay for online credits or use the mail-in form.
Please respond within 30 days, or else your account will be marked ‘discard all incoming bureau cards’.

If you would NOT like to receive incoming bureau cards, please let me know.

If you have any questions or concerns, please reply to this email or email me at ARS.kevin@na7kr.us.

You can read more about the 7th district QSL bureau at https://wvdxc.org/qsl-bureau-faq.
            </pre>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
include("$root/backend/footer.php");
?>
