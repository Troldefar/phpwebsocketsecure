export default {
    init: function() {
        this.ws = new WebSocket("wss://YOUR_HOST:12345");
    },
    handler: function() {

        const self = this;

        this.ws.onopen = function(event) {
            console.log("Connection established");
        };

        this.ws.onmessage = function(event) {
            let message = event.data;
            console.log(message);
            self.sendMessage(new Date().toLocaleTimeString());
        };

        this.ws.onclose = function(event) {
            console.log("Connection closed");
        };

        this.ws.onerror = function(error) {
            console.error(error);
        }
    },
    sendMessage: function(message) {
        this.ws.send(message);
    }
}