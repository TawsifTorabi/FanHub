<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Example</title>
</head>
<body>

<h1>WebSocket Example</h1>

<input type="text" id="messageInput" placeholder="Type your message">
<button onclick="sendMessage()">Send Message</button>

<script>
    const socket = new WebSocket('ws://192.168.1.102:80/remote');

    // Event handler for when the WebSocket connection is opened
    socket.addEventListener('open', (event) => {
        console.log('WebSocket connection opened:', event);

        // Send a ping every 2 seconds
        setInterval(() => {
            const timestamp = new Date().toISOString();
            const pingMessage = `ping ${timestamp}`;
            socket.send(pingMessage);
            console.log('Sent ping with timestamp:', timestamp);
        }, 2000);
    });

    // Event handler for receiving messages from the WebSocket server
    socket.addEventListener('message', (event) => {
        console.log('Received message:', event.data);
    });

    // Event handler for when an error occurs with the WebSocket connection
    socket.addEventListener('error', (event) => {
        console.error('WebSocket error:', event);
    });

    // Event handler for when the WebSocket connection is closed
    socket.addEventListener('close', (event) => {
        console.log('WebSocket connection closed:', event);
    });

    // Function to send a message through the WebSocket connection
    function sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value;

        if (message.trim() !== '') {
            socket.send(message);
            console.log('Sent message:', message);
            // Clear the input field after sending the message
            messageInput.value = '';
        } else {
            console.warn('Message cannot be empty.');
        }
    }
</script>

</body>
</html>
