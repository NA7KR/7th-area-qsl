<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        form > div {
            flex: 0 0 48%;
            margin-bottom: 10px;
        }
        form > div.full-width {
            flex: 0 0 100%;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="date"], input[type="email"], input[type="tel"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        input[type="submit"], button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover, button:hover {
            background-color: #218838;
        }
        .result {
            margin-top: 20px;
            background: #e9ecef;
            padding: 10px;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Submit Your Information</h2>
    <form method="post" id="dataForm">
        <div><label for="callsign">Callsign</label><input type="text" id="callsign" name="callsign" required></div>
        <div><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" required></div>
        <div><label for="last_name">Last Name</label><input type="text" id="last_name" name="last_name" required></div>
        <div><label for="class">Class</label><input type="text" id="class" name="class"></div>
        <div><label for="date_start">Start Date</label><input type="date" id="date_start" name="date_start"></div>
        <div><label for="date_exp">Expiration Date</label><input type="date" id="date_exp" name="date_exp"></div>
        <div><label for="new_call">New Call</label><input type="text" id="new_call" name="new_call"></div>
        <div><label for="old_call">Old Call</label><input type="text" id="old_call" name="old_call"></div>
        <div class="full-width"><label for="address">Address</label><input type="text" id="address" name="address"></div>
        <div class="full-width"><label for="address2">Address 2</label><input type="text" id="address2" name="address2"></div>
        <div><label for="city">City</label><input type="text" id="city" name="city"></div>
        <div><label for="state">State</label><input type="text" id="state" name="state"></div>
        <div><label for="zip">Zip</label><input type="text" id="zip" name="zip"></div>
        <div><label for="email">Email</label><input type="email" id="email" name="email"></div>
        <div><label for="phone">Phone</label><input type="tel" id="phone" name="phone"></div>
        <div class="full-width"><input type="submit" value="Submit"></div>
        <div class="full-width"><button type="button" onclick="fetchQRZData()">Fetch Data from QRZ</button></div>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $callsign = htmlspecialchars($_POST['callsign']);
        $first_name = htmlspecialchars($_POST['first_name']);
        $last_name = htmlspecialchars($_POST['last_name']);
        $class = htmlspecialchars($_POST['class']);
        $date_start = htmlspecialchars($_POST['date_start']);
        $date_exp = htmlspecialchars($_POST['date_exp']);
        $new_call = htmlspecialchars($_POST['new_call']);
        $old_call = htmlspecialchars($_POST['old_call']);
        $address = htmlspecialchars($_POST['address']);
        $address2 = htmlspecialchars($_POST['address2']);
        $city = htmlspecialchars($_POST['city']);
        $state = htmlspecialchars($_POST['state']);
        $zip = htmlspecialchars($_POST['zip']);
        $email = htmlspecialchars($_POST['email']);
        $phone = htmlspecialchars($_POST['phone']);

        echo "<div class='result'>";
        echo "<h3>Submitted Data:</h3>";
        echo "Callsign: $callsign<br>";
        echo "First Name: $first_name<br>";
        echo "Last Name: $last_name<br>";
        echo "Class: $class<br>";
        echo "Start Date: $date_start<br>";
        echo "Expiration Date: $date_exp<br>";
        echo "New Call: $new_call<br>";
        echo "Old Call: $old_call<br>";
        echo "Address: $address<br>";
        echo "Address 2: $address2<br>";
        echo "City: $city<br>";
        echo "State: $state<br>";
        echo "Zip: $zip<br>";
        echo "Email: $email<br>";
        echo "Phone: $phone<br>";
        echo "</div>";
    }
    ?>
</div>

<script>
function fetchQRZData() {
    const callsign = document.getElementById('callsign').value;
    if (!callsign) {
        alert('Please enter a callsign to fetch data from QRZ.');
        return;
    }
    
    const apiKey = 'YOUR_QRZ_API_KEY'; // Replace with your QRZ API key
    const url = `https://www.qrz.com/xml/current/?s=${apiKey}&callsign=${callsign}`;

    fetch(url)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(data, "text/xml");

            const error = xmlDoc.getElementsByTagName("Error")[0];
            if (error) {
                alert(`Error fetching data from QRZ: ${error.textContent}`);
                return;
            }

            const firstName = xmlDoc.getElementsByTagName("fname")[0]?.textContent || '';
            const lastName = xmlDoc.getElementsByTagName("name")[0]?.textContent || '';
            const address = xmlDoc.getElementsByTagName("addr1")[0]?.textContent || '';
            const city = xmlDoc.getElementsByTagName("city")[0]?.textContent || '';
            const state = xmlDoc.getElementsByTagName("state")[0]?.textContent || '';
            const zip = xmlDoc.getElementsByTagName("zip")[0]?.textContent || '';
            const email = xmlDoc.getElementsByTagName("email")[0]?.textContent || '';
            const phone = xmlDoc.getElementsByTagName("phone")[0]?.textContent || '';

            document.getElementById('first_name').value = firstName;
            document.getElementById('last_name').value = lastName;
            document.getElementById('address').value = address;
            document.getElementById('city').value = city;
            document.getElementById('state').value = state;
            document.getElementById('zip').value = zip;
            document.getElementById('email').value = email;
            document.getElementById('phone').value = phone;
        })
        .catch(error => {
            console.error('Error fetching data from QRZ:', error);
            alert('Failed to fetch data from QRZ. Please check the console for details.');
        });
}
</script>

</body>
</html>
