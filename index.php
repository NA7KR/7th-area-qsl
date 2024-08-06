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
$title = "Start Page";

// Load configuration
$config = include('config.php');

// Include header
$headerPath = "$root/backend/header.php";
if (file_exists($headerPath)) {
    include($headerPath);
} else {
    echo "Header file not found!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="/path/to/your/css/styles.css">
</head>
<body>

<div class="center-content">
    <img src="/7thArea.png" alt="7th Area" />

    <h1 class="my-4 text-center">7th Area QSL Bureau Sorters</h1>

    <table class="tg">
        <thead>
            <tr>
                <th>Section</th>
                <th>Name</th>
                <th>Call</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sorters = [
                ['A', 'Allen Lewey', 'K7ABL', 'alewey@msn.com'],
                ['B', 'Kevin Bier', 'K7VI', 'ars.k7vi@gmail.com'],
                ['C', 'Ron Vincent', 'WJ7R', 'wj7r@comcast.net'],
                ['D', 'Steve Sterling', 'WA7DUH', 'wa7duh@gmail.com'],
                ['E', 'Mike Willis', 'N6LO', 'N6LO7Buro@yahoo.com'],
                ['F', 'Kevin and Carrigan Roberts', 'NA7KR', 'ars.na7kr@na7kr.us'],
                ['G', 'Rick Smith', 'KT7G', 'zk1ttg@gmail.com'],
                ['H', 'Howard Saxion', 'WX7HS', 'howardsaxion@mac.com'],
                ['I', 'Jason Dinsmore', 'K7BPM', 'k7bpm@dinjas.com'],
                ['J', 'Jim Fenstermaker', 'K9JF', 'k9jf@k9jf.com'],
                ['K', 'Craig Cook', 'N7OR', 'qsl7thk@gmail.com'],
                ['L', 'Casey Baldwin', 'WC7L', 'ARS.WC7L@gmail.com'],
                ['M', 'Brian Phipps', 'W7BDP', 'brian.w7bdp@gmail.com'],
                ['N', 'Frank Gruber', 'KB7NJV', 'gruberfrankr@comcast.net'],
                ['O', 'Rick Aragon', 'NE7O', 'ne7o@yahoo.com'],
                ['P', 'Russ Mickiewicz', 'N7QR', 'QSL7thP@yahoo.com'],
                ['Q', 'Bernd Peters', 'KB7AK', 'bernd1peters@gmail.com'],
                ['R', 'Marilyn Miller', 'K7YL', 'k7yl.08@gmail.com'],
                ['S', 'Bob Norin', 'W7YAQ', 'w7yaq@arrl.net'],
                ['T', 'John Gohndrone', 'N7TT', 'n7tt@tds.net'],
                ['U', 'Delvin Bunton', 'NS7U', 'drbunton@comcast.net'],
                ['V', 'Scott Rosenfeld', 'N7JI', 'ars.n7ji@gmail.com'],
                ['W', 'Don Tucker', 'W7WLL', 'w7wll@arrl.net'],
                ['X', 'Dave Tucker', 'KA6BIM', 'ka6bim@arrl.net'],
                ['Y', 'Jim Cassidy', 'KI7Y', 'ki7y@arrl.net'],
                ['Z', 'Al Rovner', 'K7AR', 'k7ar@comcast.net'],
            ];

            foreach ($sorters as $sorter) {
                echo "<tr><td>{$sorter[0]}</td><td>{$sorter[1]}</td><td>{$sorter[2]}</td><td>{$sorter[3]}</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php
// Include footer
$footerPath = "$root/backend/footer.php";
if (file_exists($footerPath)) {
    include($footerPath);
} else {
    echo "Footer file not found!";
}
?>

</body>
</html>
