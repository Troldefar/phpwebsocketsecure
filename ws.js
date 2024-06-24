let socket = new WebSocket("wss://YOUR_HOST:12345");

socket.onopen = function(event) {
    console.log("Connection established");
};

socket.onmessage = function(event) {
    let message = event.data;
    console.log("Received: " + message);
    let messagesDiv = document.getElementById("messages");
    messagesDiv.innerHTML += "<p>" + message + "</p>";
};

socket.onclose = function(event) {
    console.log("Connection closed");
};

socket.onerror = function(error) {
    console.error("WebSocket error: " + error);
};

function sendMessage() {
    let messageInput = document.getElementById("message");
    let message = messageInput.value;
    socket.send(message);
    console.log("Sent: " + message);
}