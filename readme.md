# Barebone Websocket Secure 
#### Implemented in PHP with a minor frontend

# Usage 
nohup php ws.php &

From the frontend follow websocket.js and let it handle the request response cyclus
If you want to dispatch events from the backend you can go

```
(new Connector())->sendToServer(json_encode([
    'type' => 'update', 'message' => 'whatever, 'clientID' => 'someID'
]));
```

# Info
#### checkPortUsage really is only for debugging atm, so please use with caution
#### Free of charge - Hope it helps someone wanting a POC