<?php

namespace app\core\src\websocket;

class MessageHandler {

    private FrameHandler $frameHandler;

    public function __construct() {
        $this->frameHandler = new FrameHandler();
    }
    
    public function handleMessage($client, $data) {
        $data = $this->frameHandler->decodeFrame($data);
        var_dump($client, $data);
        Logger::yell('Received from client: ' . $data . PHP_EOL);
    }

    public function broadcastMessage(array $clients, $message) {
        foreach ($clients as $client) 
            fwrite($client, chr(129) . chr(strlen($message)) . $message);
    }

    public function messageClient($client, $message) {
        fwrite($client, chr(129) . chr(strlen($message)) . $message);
    }
}
