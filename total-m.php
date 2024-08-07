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
$title = "Cards on Hand";

include_once("$root/config.php");
include("$root/backend/header.php");

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

function processData($rawData, $keyIndexes) {
    $data = [];
    foreach ($rawData as $row) {
        $columns = str_getcsv($row);
        $key = $columns[$keyIndexes['keyIndex']];
        $value = (int)$columns[$keyIndexes['valueIndex']];
        if (isset($data[$key])) {
            $data[$key] += $value;
        } else {
            $data[$key] = $value;
        }
    }
    return $data;
}

function getData($dbPath, $tableName, $keyIndexes) {
    $rawData = fetchData($dbPath, $tableName);
    if (!empty($rawData)) {
        $headers = normalizeHeaders(str_getcsv(array_shift($rawData)));
        $keyIndexes['keyIndex'] = array_search($keyIndexes['keyName'], $headers);
        $keyIndexes['valueIndex'] = array_search($keyIndexes['valueName'], $headers);
        return processData($rawData, $keyIndexes);
    }
    return [];
}

$selectedLetter = null;
$cardData = [];
$mailedData = [];
$returnedData = [];
$moneyData = [];
$operatorData = [];
$totalCardsReceived = 0;
$totalCardsMailed = 0;
$totalCardsReturned = 0;
$totalMoneyReceived = 0;
$totalCardsOnHand = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    if (isset($config['sections'][$selectedLetter])) {
        $dbPath = $config['sections'][$selectedLetter];

        $cardData = getData($dbPath, 'tbl_CardRec', ['keyName' => 'Call', 'valueName' => 'CardsReceived']);
        $totalCardsReceived = array_sum($cardData);

        $mailedData = getData($dbPath, 'tbl_CardM', ['keyName' => 'Call', 'valueName' => 'CardsMailed']);
        $totalCardsMailed = array_sum($mailedData);

        $returnedData = getData($dbPath, 'tbl_CardRet', ['keyName' => 'Call', 'valueName' => 'CardsReturned']);
        $totalCardsReturned = array_sum($returnedData);

        $moneyData = getData($dbPath, 'tbl_MoneyR', ['keyName' => 'Call', 'valueName' => 'MoneyReceived']);
        $totalMoneyReceived = array_sum($moneyData);

        $rawOperatorData = fetchData($dbPath, 'tbl_Operator');
        if (!empty($rawOperatorData)) {
            $headers = normalizeHeaders(str_getcsv(array_shift($rawOperatorData)));
            $callIndex = array_search('Call', $headers);
            $mailInstIndex = array_search('Mail-Inst', $headers);

            foreach ($rawOperatorData as $row) {
                $columns = str_getcsv($row);
                $call = $columns[$callIndex];
                $mailInst = strtolower(trim($columns[$mailInstIndex]));
                $operatorData[$call] = $mailInst;
            }
        }

        $greenData = [];
        $redData = [];
        foreach ($cardData as $call => $cardsReceived) {
            $cardsMailed = isset($mailedData[$call]) ? $mailedData[$call] : 0;
            $cardsReturned = isset($returnedData[$call]) ? $returnedData[$call] : 0;
            $cardsOnHand = $cardsReceived - $cardsMailed - $cardsReturned;
            $totalCardsOnHand += $cardsOnHand;
            $moneyReceived = isset($moneyData[$call]) ? $moneyData[$call] : 0;
            $mailInst = isset($operatorData[$call]) ? $operatorData[$call] : 'full';

            $entry = [
                'Call' => $call,
                'CardsReceived' => $cardsReceived,
                'CardsMailed' => $cardsMailed,
                'CardsReturned' => $cardsReturned,
                'CardsOnHand' => $cardsOnHand,
                'MailInst' => $mailInst
            ];

            if ($moneyReceived > 0) {
                $greenData[] = $entry;
            } else {
                $redData[] = $entry;
            }
        }

        usort($greenData, function($a, $b) {
            return strcasecmp($a['Call'], $b['Call']);
        });

        usort($redData, function($a, $b) {
            return strcasecmp($a['Call'], $b['Call']);
        });

        $monthlyData = array_filter($greenData, function($entry) {
            return $entry['MailInst'] === 'monthly';
        });

        $quarterlyData = array_filter($greenData, function($entry) {
            return $entry['MailInst'] === 'quarterly';
        });

        $fullData = array_filter($greenData, function($entry) {
            return $entry['MailInst'] === 'full';
        });
    } else {
        echo "Error: Invalid database configuration.";
    }
}
?>

<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />

    <h1 class="my-4 text-center">7th Area QSL Bureau</h1>

    <form method="POST">
        <label for="letter">Select a Section:</label>
        <select name="letter" id="letter">
            <?php foreach ($config['sections'] as $letter => $dbPath): ?>
                <option value="<?= htmlspecialchars($letter) ?>" <?= $selectedLetter === $letter ? 'selected' : '' ?>>
                    <?= htmlspecialchars($letter) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($monthlyData) || !empty($quarterlyData) || !empty($fullData) || !empty($redData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Cards on Hand: <?= $totalCardsOnHand ?></p>

        <?php if (!empty($monthlyData)): ?>
            <h3>Monthly Table</h3>
            <table>
                <thead>
                    <tr>
                        <th>Call</th>
                       
                        <th>Cards On Hand</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyData as $row): ?>
                        <tr>
                            <td class="green"><?= htmlspecialchars($row['Call']) ?></td>
                         
                            <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($quarterlyData)): ?>
            <h3>Quarterly Table</h3>
            <table>
                <thead>
                    <tr>
                        <th>Call</th>
                     
                        <th>Cards On Hand</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quarterlyData as $row): ?>
                        <tr>
                            <td class="green"><?= htmlspecialchars($row['Call']) ?></td>
                        
                            <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($fullData)): ?>
            <h3>Full Table</h3>
            <table>
                <thead>
                    <tr>
                        <th>Call</th>
                      
                        <th>Cards On Hand</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fullData as $row): ?>
                        <tr>
                            <td class="green"><?= htmlspecialchars($row['Call']) ?></td>
                           
                            <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($redData)): ?>
            <h3>Not Paid Table</h3>
            <table>
                <thead>
                    <tr>
                        <th>Call</th>
                     
                        <th>Cards On Hand</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($redData as $row): ?>
                        <tr>
                            <td class="red"><?= htmlspecialchars($row['Call']) ?></td>
                       
                            <td><?= htmlspecialchars($row['CardsOnHand']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>

<?php
include("$root/backend/footer.php");
?>
