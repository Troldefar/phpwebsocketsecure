<?php

namespace ws\websocket\src;

class MessageHandler {

    private FrameHandler $frameHandler;

    public function __construct() {
        $this->frameHandler = new FrameHandler();
    }
    
    public function handleMessage($client, $data) {
        $data = $this->frameHandler->decodeFrame($data);
        return $data;
    }

    public function broadcastMessage(array $clients, $message) {
        foreach ($clients as $client)
            fwrite($client, chr(Constants::FINAL_TEXT_FRAME) . chr(strlen($message)) . $message);
    }

    public function messageClient($client, string $message) {
        fwrite($client, $this->frameHandler->encodeWebSocketFrame($message));
    }
}