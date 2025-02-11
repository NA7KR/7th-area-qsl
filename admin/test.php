<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Flashing Message Demo</title>
  <!-- Optional: Include Bootstrap for additional styling -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    /* Style for the message container */
    #messageDiv {
      text-align: center;
      font-size: 1.5em;
      display: none;  /* Hidden by default */
      margin: 20px auto; /* Center the div horizontally if a width is set */
      /* Optionally, add a width if needed, e.g., width: 300px; */
    }

    /* Define the flashing red text animation */
    @keyframes flashRed {
      0%, 100% { color: red; }
      50% { color: black; }
    }

    /* Apply the animation when the class is added */
    .flash {
      animation: flashRed 1s infinite;
    }
  </style>
</head>
<body>
  <!-- The element where the message will be displayed -->
  <div id="messageDiv"></div>

  <script>
    // JavaScript function to display the message and add the flash animation
    function displayMessage(message) {
      const messageDiv = document.getElementById('messageDiv');
      if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.style.display = 'block';
        messageDiv.classList.add('flash'); // Start the flash animation
      }
    }
  </script>

  <?php
    // For testing purposes, you can assign a value here.
    // In your application, $msgecho may come from elsewhere.
    $msgecho = "This is a flashing red message!";
    if (!empty($msgecho)):
  ?>
    <script>
      // Use json_encode to safely pass the PHP string to JavaScript.
      displayMessage(<?php echo json_encode(htmlspecialchars($msgecho)); ?>);
    </script>
  <?php endif; ?>
</body>
</html>
