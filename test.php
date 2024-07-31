<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QRZ API Test</title>
</head>
<body>
    <input type="text" id="callsign" placeholder="Enter callsign">
    <button id="fetchDataButton">Fetch Data</button>

    <?php
    // Include the configuration file
    $config = include('config.php');
    ?>

    <script>
        document.getElementById('fetchDataButton').addEventListener('click', function() {
            const callsign = document.getElementById('callsign').value;
            if (!callsign) {
                alert('Please enter a callsign to fetch data from QRZ.');
                return;
            }

            // PHP Configuration values echoed to JavaScript
            const apikey = <?php echo json_encode($config['qrz_api']['key']); ?>;
            const apicall = <?php echo json_encode($config['qrz_api']['callsign']); ?>;

            // Debugging the values
            console.log(`API Key: ${apikey}`);
            console.log(`API Call Sign: ${apicall}`);
            console.log(`Call Sign: ${callsign}`);

            // Construct the initial URL to get the session key
            const initialUrl = `https://xmldata.qrz.com/xml/current/?username=${apicall}&password=${apikey}`;
            console.log(`Initial URL: ${initialUrl}`);

            // Fetching data from QRZ API to get the session key
            fetch(initialUrl)
                .then(response => response.text())
                .then(data => {
                    console.log('Initial response:', data);
                    const sessionKey = extractSessionKey(data);
                    if (sessionKey) {
                        fetchCallsignData(sessionKey, callsign);
                    } else {
                        console.error('Failed to retrieve session key.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching initial data:', error);
                });
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
            console.log(`Constructed URL: ${url}`);

            // Fetching data from QRZ API using the session key and callsign
            fetch(url)
                .then(response => response.text())
                .then(data => {
                    console.log('Raw response:', data);
                    parseXML(data);
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                });
        }

        function parseXML(xml) {
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xml, "text/xml");

            const error = xmlDoc.getElementsByTagName("Error")[0];
            if (error) {
                alert(`Error: ${error.textContent}`);
                return;
            }

            // Recursive function to iterate through XML nodes and log their content
            function traverseXML(node, indent = 0) {
                let padding = " ".repeat(indent);
                console.log(`${padding}${node.nodeName}: ${node.textContent.trim()}`);

                // Recursively process child nodes
                for (let i = 0; i < node.children.length; i++) {
                    traverseXML(node.children[i], indent + 2);
                }
            }

            // Start traversing from the root element
            traverseXML(xmlDoc.documentElement);
        }
    </script>
</body>
</html>
