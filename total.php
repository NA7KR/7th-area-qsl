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
 
 // Initialize variables
 $selectedLetter = null;
 $cardData = [];
 $totalCardsReceived = 0;
 $startDate = null;
 $endDate = null;
 
 if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     // Sanitize user input
     $selectedLetter = filter_input(INPUT_POST, 'letter', FILTER_SANITIZE_SPECIAL_CHARS);
     $startDate = filter_input(INPUT_POST, 'startDate', FILTER_SANITIZE_SPECIAL_CHARS);
     $endDate = filter_input(INPUT_POST, 'endDate', FILTER_SANITIZE_SPECIAL_CHARS);
 
     if ($selectedLetter && isset($config['sections'][$selectedLetter])) {
         $dbConfig = $config['sections'][$selectedLetter] ?? null;
 
         if ($dbConfig) {
             $host = $dbConfig['host'];
             $dbname = $dbConfig['dbname'];
             $username = $dbConfig['username'];
             $password = $dbConfig['password'];
 
             try {
                 $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
                 $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
             } catch (PDOException $e) {
                 die("Connection failed: " . $e->getMessage());
             }
 
             function fetchData($pdo, $tableName, $keyName, $valueName, $startDate, $endDate) {
                 $query = "SELECT `$keyName`, `$valueName` FROM `$tableName`";
                 $conditions = [];
 
                 // Use DateReceived for filtering
                 $dateColumn = 'DateReceived';
 
                 if (!empty($startDate)) {
                     $conditions[] = "`$dateColumn` >= :startDate";
                 }
                 if (!empty($endDate)) {
                     $conditions[] = "`$dateColumn` <= :endDate";
                 }
 
                 if ($conditions) {
                     $query .= " WHERE " . implode(' AND ', $conditions);
                 }
 
                 $stmt = $pdo->prepare($query);
 
                 // Bind parameters
                 if (!empty($startDate)) {
                     $stmt->bindValue(':startDate', $startDate);
                 }
                 if (!empty($endDate)) {
                     $stmt->bindValue(':endDate', $endDate);
                 }
 
                 // Debugging: Output the query with bound parameters
                 $debugQuery = $query;
                 $debugQuery = str_replace(':startDate', $pdo->quote($startDate), $debugQuery);
                 $debugQuery = str_replace(':endDate', $pdo->quote($endDate), $debugQuery);
                //echo "Executing Query: $debugQuery<br>";
 
                 try {
                     $stmt->execute();
                     return $stmt->fetchAll(PDO::FETCH_ASSOC);
                 } catch (PDOException $e) {
                     die("SQL Error: " . $e->getMessage());
                 }
             }
 
             // Fetch data using the DateReceived filter
             $cardData = fetchData($pdo, 'tbl_CardRec', 'Call', 'CardsReceived', $startDate, $endDate);
             foreach ($cardData as $row) {
                 $totalCardsReceived += $row['CardsReceived'];
             }
 
         } else {
             echo "Error: Database configuration for the selected section not found.";
         }
     } else {
         echo "Error: Invalid letter selected.";
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
             <input type="date" id="startDate" name="startDate" value="<?= htmlspecialchars($startDate ?? '') ?>">
             
             <label for="endDate">End Date:</label>
             <input type="date" id="endDate" name="endDate" value="<?= htmlspecialchars($endDate ?? '') ?>">
         </div>
         <button type="submit">Submit</button>
     </form>
 
     <?php if ($selectedLetter && !empty($cardData)): ?>
         <h2>Section <?= htmlspecialchars($selectedLetter) ?></h2>
         <p>Total Cards Received: <?= $totalCardsReceived ?></p>
         <table>
             <thead>
                 <tr>
                     <th>Call</th>
                     <th>Cards Received</th>
                 </tr>
             </thead>
             <tbody>
                 <?php foreach ($cardData as $row): ?>
                     <tr>
                         <td><?= htmlspecialchars($row['Call']) ?></td>
                         <td><?= htmlspecialchars($row['CardsReceived']) ?></td>
                     </tr>
                 <?php endforeach; ?>
             </tbody>
         </table>
     <?php elseif ($selectedLetter): ?>
         <p>No data found for the selected section and date range.</p>
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
 