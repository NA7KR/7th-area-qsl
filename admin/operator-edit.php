<?php
session_start();

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Page to Edit Operator";
$config = include($root . '/config.php');

include("$root/backend/header.php"); 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
?>
<div class="center-content">
<img src="/7thArea.png" alt="7th Area" />

</div>
<div class="container">
    <h2>Submit Your Information</h2>
    <form method="post" id="dataForm">
        <div><label for="callsign">Callsign</label><input type="text" id="callsign" name="callsign" required></div>
        <div><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" required></div>
        <div><label for="last_name">Last Name</label><input type="text" id="last_name" name="last_name" required></div>
        <div><label for="class">Class</label><input type="text" id="class" name="class"></div>
        <div><label for="date_start">Start Date</label><input type="text" id="date_start" name="date_start"></div>
        <div><label for="date_exp">Expiration Date</label><input type="text" id="date_exp" name="date_exp"></div>
        <div><label for="new_call">New Call</label><input type="text" id="new_call" name="new_call"></div>
        <div><label for="old_call">Old Call</label><input type="text" id="old_call" name="old_call"></div>
        <div class="full-width"><label for="address">Address</label><input type="text" id="address" name="address"></div>
        <div class="full-width"><label for="address2">Address 2</label><input type="text" id="address2" name="address2"></div>
        <div><label for="city">City</label><input type="text" id="city" name="city"></div>
        <div><label for="state">State</label><input type="text" id="state" name="state"></div>
        <div><label for="zip">Zip</label><input type="text" id="zip" name="zip"></div>
        <div><label for="country">Country</label><input type="text" id="country" name="country"></div>
        <div><label for="email">Email</label><input type="email" id="email" name="email"></div>
        <div><label for="phone">Phone</label><input type="tel" id="phone" name="phone"></div>
        <div><label for="born">Born</label><input type="text" id="born" name="born"></div>
        <div class="full-width">
            <input type="submit" value="Submit" id="submitButton" disabled>
        </div>
        <div class="full-width">
            <button type="button" id="fetchButton">Fetch Data from QRZ</button>
        </div>
        <div class="full-width">
            <button type="button" id="clearButton">Clear</button>
        </div>
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
        $country = htmlspecialchars($_POST['country']);
        $email = htmlspecialchars($_POST['email']);
        $phone = htmlspecialchars($_POST['phone']);
        $born = htmlspecialchars($_POST['born']);

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
        echo "Country: $country<br>";
        echo "Email: $email<br>";
        echo "Phone: $phone<br>";
        echo "Born: $born<br>";
        echo "</div>";
    }
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const submitButton = document.getElementById('submitButton');
    const fetchButton = document.getElementById('fetchButton');
    const clearButton = document.getElementById('clearButton');
    const dataForm = document.getElementById('dataForm');

    fetchButton.addEventListener('click', function () {
        fetchQRZData();
    });

    clearButton.addEventListener('click', function () {
        dataForm.reset();
        submitButton.disabled = true;
        submitButton.classList.add('disabled-button');
        fetchButton.disabled = false;
        fetchButton.classList.remove('disabled-button');
    });

    function fetchQRZData() {
        const callsign = document.getElementById('callsign').value;
        if (!callsign) {
            alert('Please enter a callsign to fetch data from QRZ.');
            return;
        }

        fetchButton.disabled = true;  // Disable fetch button after clicking
        fetchButton.classList.add('disabled-button');

        const apikey = <?php echo json_encode($config['qrz_api']['key']); ?>;
        const apicall = <?php echo json_encode($config['qrz_api']['callsign']); ?>;

        const initialUrl = `https://xmldata.qrz.com/xml/current/?username=${apicall}&password=${apikey}`;

        fetch(initialUrl)
            .then(response => response.text())
            .then(data => {
                const sessionKey = extractSessionKey(data);
                if (sessionKey) {
                    fetchCallsignData(sessionKey, callsign);
                } else {
                    console.error('Failed to retrieve session key.');
                    fetchButton.disabled = false;  // Re-enable fetch button on error
                    fetchButton.classList.remove('disabled-button');
                }
            })
            .catch(error => {
                console.error('Error fetching initial data:', error);
                fetchButton.disabled = false;  // Re-enable fetch button on error
                fetchButton.classList.remove('disabled-button');
            });

        function extractSessionKey(xml) {
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xml, "text/xml");
            const keyNode = xmlDoc.getElementsByTagName("Key")[0];
            if (keyNode) {
                return keyNode.textContent.trim();
            } else {
                const errorNode = xmlDoc.getElementsByTagName("Error")[0];
                if (errorNode) {
                    alert(`Error: ${errorNode.textContent.trim()}`);
                }
                return null;
            }
        }

        function fetchCallsignData(sessionKey, callsign) {
            const url = `https://xmldata.qrz.com/xml/current/?s=${sessionKey};callsign=${callsign}`;

            fetch(url)
                .then(response => response.text())
                .then(data => {
                    parseXML(data);
                    submitButton.disabled = false;
                    submitButton.classList.remove('disabled-button');
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    fetchButton.disabled = false;  // Re-enable fetch button on error
                    fetchButton.classList.remove('disabled-button');
                });
        }

        function parseXML(xml) {
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xml, "text/xml");

            const error = xmlDoc.getElementsByTagName("Error")[0];
            if (error) {
                alert(`Error: ${error.textContent}`);
                fetchButton.disabled = false;  // Re-enable fetch button on error
                fetchButton.classList.remove('disabled-button');
                return;
            }

            function removeInitials(name) {
                return name.replace(/(?:\s[A-Z]\.?)+$/, '').trim();
            }

            const firstNameNode = xmlDoc.getElementsByTagName("fname")[0];
            if (firstNameNode) {
                document.getElementById('first_name').value = removeInitials(firstNameNode.textContent.trim());
            }

            const callNode = xmlDoc.getElementsByTagName("call")[0];
            if (callNode) {
                document.getElementById('callsign').value = callNode.textContent.trim();
            }

            const lastNameNode = xmlDoc.getElementsByTagName("name")[0];
            if (lastNameNode) {
                document.getElementById('last_name').value = lastNameNode.textContent.trim();
            }

            const addr1Node = xmlDoc.getElementsByTagName("addr1")[0];
            if (addr1Node) {
                document.getElementById('address').value = addr1Node.textContent.trim();
            }

            const addr2Node = xmlDoc.getElementsByTagName("addr2")[0];
            if (addr2Node) {
                document.getElementById('city').value = addr2Node.textContent.trim();
            }

            const stateNode = xmlDoc.getElementsByTagName("state")[0];
            if (stateNode) {
                document.getElementById('state').value = stateNode.textContent.trim();
            }

            const zipNode = xmlDoc.getElementsByTagName("zip")[0];
            if (zipNode) {
                document.getElementById('zip').value = zipNode.textContent.trim();
            }

            const countryNode = xmlDoc.getElementsByTagName("country")[0];
            if (countryNode) {
                document.getElementById('country').value = countryNode.textContent.trim();
            }

            const efdateNode = xmlDoc.getElementsByTagName("efdate")[0];
            if (efdateNode) {
                document.getElementById('date_start').value = efdateNode.textContent.trim();
            }

            const expdateNode = xmlDoc.getElementsByTagName("expdate")[0];
            if (expdateNode) {
                document.getElementById('date_exp').value = expdateNode.textContent.trim();
            }

            const classNode = xmlDoc.getElementsByTagName("class")[0];
            if (classNode) {
                const classMapping = {
                    'E': 'Extra',
                    'G': 'General',
                    'T': 'Technician',
                    'A': 'Advanced',
                    'C': 'Club'
                };
                document.getElementById('class').value = classMapping[classNode.textContent.trim()] || classNode.textContent.trim();
            }

            const emailNode = xmlDoc.getElementsByTagName("email")[0];
            if (emailNode) {
                document.getElementById('email').value = emailNode.textContent.trim();
            }

            const bornNode = xmlDoc.getElementsByTagName("born")[0];
            if (bornNode) {
                document.getElementById('born').value = bornNode.textContent.trim();
            }
        }
    }
});
</script>

<?php
$footerPath = "$root/backend/footer.php";
if (file_exists($footerPath)) {
    include($footerPath);
} else {
    echo "Footer file not found!";
}
?>
</body>
</html>
