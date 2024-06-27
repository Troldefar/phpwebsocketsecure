<?php

class MessageHandler {
    public function handleMessage($client, $data) {
        echo "Received from client: $data\n";
    }

    public function broadcastMessage(array $clients, $message) {
        $response = chr(129) . chr(strlen($message)) . $message;
        foreach ($clients as $client) {
            fwrite($client, $response);
            echo "Sent: $message\n";
        }
    }
}
