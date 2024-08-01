<script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('menu-links').classList.toggle('active');
        });

        document.querySelectorAll('.dropdown > a').forEach(function(element) {
            element.addEventListener('click', function(event) {
                event.preventDefault();
                var dropdownContent = this.nextElementSibling;
                if (dropdownContent.style.display === 'block') {
                    dropdownContent.style.display = 'none';
                } else {
                    dropdownContent.style.display = 'block';
                }
            });
        });

        
        function toggleCheckbox(element) {
            var checkboxes = document.querySelectorAll('input[name="selected_calls[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = element.checked;
            });
        }

        function printLabels() {
            var selectedCalls = document.querySelectorAll('input[name="selected_calls[]"]:checked');
            var labelsDiv = document.getElementById('labels');
            labelsDiv.innerHTML = '';

            selectedCalls.forEach(function(checkbox) {
                var row = checkbox.closest('tr');
                var call = row.querySelector('.call').innerText;
                var firstName = row.querySelector('.first-name').innerText;
                var lastName = row.querySelector('.last-name').innerText;
                var address = row.querySelector('.address').innerText;
                var city = row.querySelector('.city').innerText;
                var state = row.querySelector('.state').innerText;
                var zip = row.querySelector('.zip').innerText;

                var labelDiv = document.createElement('div');
                labelDiv.className = 'label';
                labelDiv.innerHTML = `<strong>${call}</strong><br>${firstName} ${lastName}<br>${address}<br>${city}<br>${state} ${zip}`;
                labelsDiv.appendChild(labelDiv);
            });

            if (labelsDiv.innerHTML.trim() === '') {
                alert('No labels to print');
                return;
            }

            var originalContents = document.body.innerHTML;
            var printContents = labelsDiv.outerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }
        document.addEventListener('DOMContentLoaded', function () { 
            const menuToggle = document.querySelector('.menuToggle');
            const header = document.querySelector('header');

            menuToggle.addEventListener('click', function() {
                header.classList.toggle('active');
            });
        });
    </script>