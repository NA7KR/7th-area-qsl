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
    <img src="7thArea.png" alt="7th Area" />
    <h1 class="my-4 text-center">7th Area QSL Bureau Sorters</h1>


<table class="tg">
    <thead>
        <tr>
            <th>Section</th><th>Name</th><th>Call</th><th>Email</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>A</td><td>Allen Lewey</td><td>K7ABL</td><td>alewey@msn.com</td></tr>
        <tr><td>B</td><td>Kevin Bier</td><td>K7VI</td><td>ars.k7vi@gmail.com</td></tr>
        <tr><td>C</td><td>Ron Vincent</td><td>WJ7R</td><td>wj7r@comcast.net</td></tr>
        <tr><td>D</td><td>Steve Sterling</td><td>WA7DUH</td><td>wa7duh@gmail.com</td></tr>
        <tr><td>E</td><td>Mike Willis</td><td>N6LO</td><td>N6LO7Buro@yahoo.com</td></tr>
        <tr><td>F</td><td>Kevin and Carrigan Roberts</td><td>NA7KR</td><td>ars.na7kr@na7kr.us</td></tr>
        <tr><td>G</td><td>Rick Smith</td><td>KT7G</td><td>zk1ttg@gmail.com</td></tr>
        <tr><td>H</td><td>Howard Saxion</td><td>WX7HS</td><td>howardsaxion@mac.com</td></tr>
        <tr><td>I</td><td>Jason Dinsmore</td><td>K7BPM</td><td>k7bpm@dinjas.com</td></tr>
        <tr><td>J</td><td>Jim Fenstermaker</td><td>K9JF</td><td>k9jf@k9jf.com</td></tr>
        <tr><td>K</td><td>Craig Cook</td><td>N7OR</td><td>qsl7thk@gmail.com</td></tr>
        <tr><td>L</td><td>Casey Baldwin</td><td>WC7L</td><td>ARS.WC7L@gmail.com</td></tr>
        <tr><td>M</td><td>Brian Phipps</td><td>W7BDP</td><td>brian.w7bdp@gmail.com</td></tr>
        <tr><td>N</td><td>Frank Gruber</td><td>KB7NJV</td><td>gruberfrankr@comcast.net</td></tr>
        <tr><td>O</td><td>Rick Aragon</td><td>NE7O</td><td>ne7o@yahoo.com</td></tr>
        <tr><td>P</td><td>Russ Mickiewicz</td><td>N7QR</td><td>QSL7thP@yahoo.com</td></tr>
        <tr><td>Q</td><td>Bernd Peters</td><td>KB7AK</td><td>bernd1peters@gmail.com</td></tr>
        <tr><td>R</td><td>Marilyn Miller</td><td>K7YL</td><td>k7yl.08@gmail.com</td></tr>
        <tr><td>S</td><td>Bob Norin</td><td>W7YAQ</td><td>w7yaq@arrl.net</td></tr>
        <tr><td>T</td><td>John Gohndrone</td><td>N7TT</td><td>n7tt@tds.net</td></tr>
        <tr><td>U</td><td>Delvin Bunton</td><td>NS7U</td><td>drbunton@comcast.net</td></tr>
        <tr><td>V</td><td>Scott Rosenfeld</td><td>N7JI</td><td>ars.n7ji@gmail.com</td></tr>
        <tr><td>W</td><td>Don Tucker</td><td>W7WLL</td><td>w7wll@arrl.net</td></tr>
        <tr><td>X</td><td>Dave Tucker</td><td>KA6BIM</td><td>ka6bim@arrl.net</td></tr>
        <tr><td>Y</td><td>Jim Cassidy</td><td>KI7Y</td><td>ki7y@arrl.net</td></tr>
        <tr><td>Z</td><td>Al Rovner</td><td>K7AR</td><td>k7ar@comcast.net</td></tr>
    </tbody>
</table>

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
