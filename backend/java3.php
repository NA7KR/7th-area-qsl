<?php
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const submitButton = document.getElementById('submitButton');
        const fetchButton = document.getElementById('fetchButton');
        const clearButton = document.querySelector('button[type="reset"]');
        const dataForm = document.getElementById('dataForm');
        const customAddressSelect = document.getElementById('customAddress');
        const messageDiv = document.getElementById('messageDiv');
        const message = <?php echo json_encode($msgecho); ?>;

        // Display the message if available
        if (message) {
            displayMessage(message);
        }

        function fetchQRZData() {
            const callsign = document.getElementById('callsign').value;
            if (!callsign) {
                alert('Please enter a callsign to fetch data from QRZ.');
                return;
            }

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
                    }
                })
                .catch(error => {
                    console.error('Error fetching initial data:', error);
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
                    })
                    .catch(error => {
                        console.error('Error fetching callsign data:', error);
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
                // After populating fields, ALWAYS enable the submit button
                submitButton.disabled = false;
                submitButton.classList.remove('disabled-button');

                // Since data was fetched, disable the fetch button
                fetchButton.disabled = true;
                fetchButton.classList.add('disabled-button');
            }
        }

        function updateButtonStates() {
            if (customAddressSelect && customAddressSelect.value === 'Custom Address') {
                submitButton.disabled = false;
                submitButton.classList.remove('disabled-button');
                fetchButton.disabled = true;
                fetchButton.classList.add('disabled-button');
            } else {
                submitButton.disabled = true;
                submitButton.classList.add('disabled-button');
                fetchButton.disabled = false;
                fetchButton.classList.remove('disabled-button');
            }
        }

        function displayMessage(message) {
            if (messageDiv) {
                messageDiv.textContent = message;
                messageDiv.style.display = 'block';
                messageDiv.classList.add('flash'); // Start the flash animation
            }
        }

        customAddressSelect.addEventListener('change', updateButtonStates);
        fetchButton.addEventListener('click', fetchQRZData);
        clearButton.addEventListener('click', function () {
            dataForm.reset();
            messageDiv.innerHTML = '';
            // Reset the custom address select if needed
            if (customAddressSelect) {
                customAddressSelect.value = 'Active';
            }
            // Reset button states
            updateButtonStates();
        });
        updateButtonStates();
    });
</script>