<?php

class MessageHandler {

    private FrameHandler $frameHandler;

    public function __construct() {
        $this->frameHandler = new FrameHandler();
    }
    
    public function handleMessage($client, $data) {
        $data = $this->frameHandler->decodeFrameDebug($data);
        Logger::yell('Received from client: ' . $data . PHP_EOL);
    }
    

    public function broadcastMessage(array $clients, $message) {
        $response = chr(129) . chr(strlen($message)) . $message;
        foreach ($clients as $client) {
            fwrite($client, $response);
            Logger::yell('Sent: ' . $message . PHP_EOL);
        }
    }
}
