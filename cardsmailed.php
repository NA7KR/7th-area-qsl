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
$title = "Cards Mailed Out";

include("$root/backend/header.php");

$config = include($root . '/config.php');

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

function parseMailedData($rawData) {
    if (empty($rawData)) {
        return [[], 0];
    }

    $headers = str_getcsv(array_shift($rawData));
    $callIndex = array_search('Call', $headers);
    $cardsMailedIndex = array_search('CardsMailed', $headers);

    $mailedData = [];
    $totalCardsMailed = 0;

    foreach ($rawData as $row) {
        $columns = str_getcsv($row);
        if ($callIndex !== false && $cardsMailedIndex !== false) {
            $cardsMailed = (int)$columns[$cardsMailedIndex];
            $mailedData[] = [
                'Call' => $columns[$callIndex],
                'CardsMailed' => $cardsMailed
            ];
            $totalCardsMailed += $cardsMailed;
        }
    }

    usort($mailedData, function($a, $b) {
        return strcasecmp($a['Call'], $b['Call']);
    });

    return [$mailedData, $totalCardsMailed];
}

function handleFormSubmission($config) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['letter'])) {
        return [null, [], 0];
    }

    $selectedLetter = $_POST['letter'];
    if (!isset($config['sections'][$selectedLetter])) {
        echo "Error: Invalid database configuration.";
        return [null, [], 0];
    }

    $dbPath = $config['sections'][$selectedLetter];
    $rawMailedData = fetchData($dbPath, 'tbl_CardM');
    return [$selectedLetter, parseMailedData($rawMailedData)];
}

list($selectedLetter, $parsedData) = handleFormSubmission($config);
$mailedData = $parsedData[0];
$totalCardsMailed = $parsedData[1];

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

    <?php if (!empty($mailedData)): ?>
        <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
        <p>Cards Mailed: <?= $totalCardsMailed ?></p>

        <table>
            <thead>
                <tr>
                    <th>Call</th>
                    <th>Cards Mailed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mailedData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Call']) ?></td>
                        <td><?= htmlspecialchars($row['CardsMailed']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selectedLetter !== null): ?>
        <p>No data found or there was an error retrieving the data.</p>
    <?php endif; ?>
</div>

<?php include("$root/backend/footer.php"); ?>
