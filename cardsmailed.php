<?php
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Cards Mailed Out";
$debug = false;

include("$root/backend/header.php");

$config = include($root . '/config.php');

function fetchData($dbPath, $tableName, $startDate = null, $endDate = null, $enableDateFilter = false, $debug = false) {
    $escapedDbPath = escapeshellarg($dbPath);
    $escapedTableName = escapeshellarg($tableName);
    
    // Build the command
    $command = "mdb-export $escapedDbPath $escapedTableName";
    if ($enableDateFilter && $startDate && $endDate) {
        $awkCommand = "awk -F',' 'NR==1 {print \$0; next} "; // Print header row
        $awkCommand .= "{";
        $awkCommand .= "split(\$6, date_time, \" \");";
        $awkCommand .= "date = date_time[1];";
        $awkCommand .= "gsub(/\"/, \"\", date);";
        $awkCommand .= "split(date, parts, \"/\");";
        $awkCommand .= "month = parts[1];";
        $awkCommand .= "day = parts[2];";
        $awkCommand .= "year = parts[3];";
        $awkCommand .= "formattedDate = year month day;";
        $awkCommand .= "if (formattedDate >= \"" . date("ymd", strtotime($startDate)) . "\" && formattedDate <= \"" . date("ymd", strtotime($endDate)) . "\")";
        $awkCommand .= "print \$0;";
        $awkCommand .= "}'";
        $command .= " | " . $awkCommand;
    }
    
    if ($debug) {
        echo "Debug: Command: " . htmlspecialchars($command) . "<br>";
    }

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

    if ($callIndex === false || $cardsMailedIndex === false) {
        echo "Error: Could not find required columns in the data.";
        return [[], 0];
    }

    $mailedData = [];
    $totalCardsMailed = 0;

    foreach ($rawData as $row) {
        $columns = str_getcsv($row);
        if (isset($columns[$callIndex]) && isset($columns[$cardsMailedIndex])) {
            $call = $columns[$callIndex];
            $cardsMailed = (int)$columns[$cardsMailedIndex];
            $mailedData[] = [
                'Call' => $call,
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

function handleFormSubmission($config, $debug) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return [null, [], 0];
    }

    $selectedLetter = $_POST['letter'] ?? null;
    $enableDateFilter = isset($_POST['dateFilterCheckbox']);
    $startDate = $_POST['startDate'] ?? null;
    $endDate = $_POST['endDate'] ?? null;

    if (!$selectedLetter || !isset($config['sections'][$selectedLetter])) {
        echo "Error: Invalid database configuration.";
        return [null, [], 0];
    }

    $dbPath = $config['sections'][$selectedLetter];
    $rawMailedData = fetchData($dbPath, 'tbl_CardM', $startDate, $endDate, $enableDateFilter, $debug);
    return [$selectedLetter, parseMailedData($rawMailedData)];
}

list($selectedLetter, $parsedData) = handleFormSubmission($config, $debug);
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

<script>
function toggleDateFilters() {
    var checkbox = document.getElementById('dateFilterCheckbox');
    var dateFilters = document.getElementById('dateFilters');
    dateFilters.style.display = checkbox.checked ? 'block' : 'none';
}
</script>

<?php include("$root/backend/footer.php"); ?>
