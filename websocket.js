window[appName].websocket = {
    init: function() {
        this.ws = new WebSocket("wss://HOST:PORT");
    },
    handler: async function() {

        const id = await $.get('/user');

        const self = this;

        this.ws.onopen = function(event) {
            console.log("Connection established");
            self.sendMessage(JSON.stringify({type: 'identifier', 'id': id.responseJSON}));
        };

        this.ws.onmessage = async function(event) {
            let message = event.data;
            self.handleServerPacket(message);
            self.sendMessage(JSON.stringify({type: 'listener', 'id': id.responseJSON}));
        };

        this.ws.onclose = function(event) {
            self.init();
            console.log("Connection closed", event);
        };

        this.ws.onerror = function(error) {
            console.error(error);
        }
    },
    handleServerPacket: async function(message) {
        console.log(message);
    },
    sendMessage: function(message) {
        this.ws.send(message);
    }
}