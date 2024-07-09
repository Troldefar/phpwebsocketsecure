<?php

class MessageHandler {

    private FrameHandler $frameHandler;

    public function __construct() {
        $this->frameHandler = new FrameHandler();
    }
    
    public function handleMessage($client, $data) {
        $data = $this->frameHandler->decodeFrame($data);
        Logger::yell(Constants::MSG_RECEIVED_FROM_CLIENT . $data . PHP_EOL);
    }

    public function broadcastMessage(array $clients, $message) {
        foreach ($clients as $client)
            fwrite($client, chr(Constants::FINAL_TEXT_FRAME) . chr(strlen($message)) . $message);
    }

    public function messageClient($client, $message) {
        fwrite($client, $this->frameHandler->encodeWebSocketFrame($message));
    }
}
