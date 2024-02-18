<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>localStorage Example</title>
</head>
<body>

<!-- Display area for output -->
<div id="output"></div>

<script>
    // Function to write data to localStorage
    function writeToLocalStorage() {
        // Get the input value
        var inputValue = document.getElementById('inputValue').value;

        // Check if input is not empty
        if (inputValue.trim() !== "") {
            // Write to localStorage
            localStorage.setItem('data', inputValue);

            // Show success message
            document.getElementById('output').innerHTML = 'Data successfully written to localStorage.';
        } else {
            // Show error message if input is empty
            document.getElementById('output').innerHTML = 'Please enter a value.';
        }
    }

    // Function to read data from localStorage
    function readFromLocalStorage() {
        // Read from localStorage
        var storedData = localStorage.getItem('data');

        // Check if data exists
        if (storedData) {
            // Display the stored data
            document.getElementById('output').innerHTML = 'Data read from localStorage: ' + storedData;
        } else {
            // Show message if no data found
            document.getElementById('output').innerHTML = 'No data found in localStorage.';
        }
    }
</script>

<!-- Input field and buttons for interaction -->
<input type="text" id="inputValue" placeholder="Enter data">
<button onclick="writeToLocalStorage()">Write to localStorage</button>
<button onclick="readFromLocalStorage()">Read from localStorage</button>

</body>
</html>
