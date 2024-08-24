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
$title = "Total Cards Received";
include("$root/backend/header.php");
$config = include($root . '/config.php');

function fetchData($dbPath, $tableName, $startDate = null, $endDate = null) {
    $dateFilter = '';
    if ($startDate && $endDate) {
        $dateFilter = " WHERE `DateReceived` BETWEEN '$startDate' AND '$endDate'";
    } elseif ($startDate) {
        $dateFilter = " WHERE `DateReceived` >= '$startDate'";
    } elseif ($endDate) {
        $dateFilter = " WHERE `DateReceived` <= '$endDate'";
    }

    $command = "mdb-export '$dbPath' '$tableName'" . $dateFilter;
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    if ($return_var !== 0) {
        echo "Error: Could not retrieve data.";
        return [];
    }
    return $output;
}

$selectedLetter = null;
$data = [];
$totalCardsOnHand = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letter'])) {
    $selectedLetter = $_POST['letter'];
    $startDate = $_POST['startDate'] ?? null;
    $endDate = $_POST['endDate'] ?? null;

    if (isset($config['sections'][$selectedLetter])) {
        $dbPath = $config['sections'][$selectedLetter];
        $rawData = fetchData($dbPath, 'tbl_CardRec', $startDate, $endDate);
        if (!empty($rawData)) {
            $headers = str_getcsv(array_shift($rawData));
            $callIndex = array_search('Call', $headers);
            $cardsReceivedIndex = array_search('CardsReceived', $headers);

            $aggregatedData = [];

            foreach ($rawData as $row) {
                $columns = str_getcsv($row);
                if ($callIndex !== false && $cardsReceivedIndex !== false) {
                    $call = $columns[$callIndex];
                    $cardsReceived = (int) $columns[$cardsReceivedIndex];
                    if (isset($aggregatedData[$call])) {
                        $aggregatedData[$call] += $cardsReceived;
                    } else {
                        $aggregatedData[$call] = $cardsReceived;
                    }
                    $totalCardsOnHand += $cardsReceived;
                }
            }

            foreach ($aggregatedData as $call => $cardsReceived) {
                $data[] = [
                    'Call' => $call,
                    'CardsReceived' => $cardsReceived
                ];
            }

            usort($data, function($a, $b) {
                return $b['CardsReceived'] <=> $a['CardsReceived'];
            });
        }
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
        
        <label for="dateFilterCheckbox">Enable Date Filter:</label>
        <input type="checkbox" id="dateFilterCheckbox" name="dateFilterCheckbox" onclick="toggleDateFilters()" <?= isset($_POST['dateFilterCheckbox']) ? 'checked' : '' ?>>

        <div id="dateFilters" style="display: <?= isset($_POST['dateFilterCheckbox']) ? 'block' : 'none' ?>;">
            <label for="startDate">Start Date:</label>
            <input type="date" id="startDate" name="startDate" value="<?= htmlspecialchars($_POST['startDate'] ?? '') ?>">
            
            <label for="endDate">End Date:</label>
            <input type="date" id="endDate" name="endDate" value="<?= htmlspecialchars($_POST['endDate'] ?? '') ?>">
        </div>
        
        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($data)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Total Cards Handled: <?= htmlspecialchars($totalCardsOnHand) ?></p>
        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>Cards Received</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Call']) ?></td>
                        <td><?= htmlspecialchars($row['CardsReceived']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>

<script>
function toggleDateFilters() {
    var checkbox = document.getElementById('dateFilterCheckbox');
    var dateFilters = document.getElementById('dateFilters');
    dateFilters.style.display = checkbox.checked ? 'block' : 'none';
}
</script>

<?php
include("$root/backend/footer.php");
?>
