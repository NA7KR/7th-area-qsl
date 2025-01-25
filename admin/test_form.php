<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Letter/Call Form</title>
</head>
<body>
    <form action="fetch_status.php" method="POST">
        <label for="letter">Letter:</label>
        <input type="text" id="selectedLetter" name="selectedLetter" placeholder="e.g., F" required>

        <label for="call">Call:</label>
        <input type="text" id="call" name="call" placeholder="e.g., K7FAZ" required>

        <button type="submit">Submit</button>
    </form>
</body>
</html>
