<?php

class Connector {

    public static function sendToServer($message = 'qwd') {
        $websocketConfigs = app()->getConfig()->get('integrations')->websocket;

        // Create SSL context for the client
        $context = stream_context_create([
            'ssl' => [
                'local_cert' => $websocketConfigs->paths->cert,
                'local_pk' => $websocketConfigs->paths->key,
                'allow_self_signed' => false,
                'verify_peer' => false,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
            ]
        ]);

        $client = stream_socket_client('ssl://'. $websocketConfigs->address .':' . $websocketConfigs->port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

        if (!$client) {
            echo "Failed to connect: $errstr ($errno)\n";
            return;
        }

        echo "Connected to WebSocket server\n";

        if (!self::performHandshake($client)) {
            fclose($client);
            return;
        }

        self::sendWebSocketMessage($client, $message);

        fclose($client);
    }

    private static function performHandshake($client) {
        $websocketConfigs = app()->getConfig()->get('integrations')->websocket;
        $key = base64_encode($websocketConfigs->sha1key);

        $request = "GET / HTTP/1.1\r\n";
        $request .= "Host: {$websocketConfigs->address}:{$websocketConfigs->port}\r\n";
        $request .= "Upgrade: websocket\r\n";
        $request .= "Connection: Upgrade\r\n";
        $request .= "Sec-WebSocket-Key: $key\r\n";
        $request .= "Sec-WebSocket-Version: 13\r\n";
        $request .= "\r\n";

        fwrite($client, $request);

        $response = fread($client, 1024);

        if (!preg_match('#Sec-WebSocket-Accept:\s(.*)$#mUsi', $response, $matches)) {
            echo "Invalid WebSocket handshake response:\n$response\n";
            return false;
        }

        return true;
    }

    private static function sendWebSocketMessage($client, $message) {
        $frame = self::encodeWebSocketFrame($message);
        fwrite($client, $frame);
    }

    private static function encodeWebSocketFrame($message) {
        $length = strlen($message);
        $frame = chr(129);

        if ($length <= 125) {
            $frame .= chr($length);
        } elseif ($length <= 65535) {
            $frame .= chr(126) . pack('n', $length);
        } else {
            $frame .= chr(127) . pack('NN', 0, $length);
        }

        $frame .= $message;

        return $frame;
    }
}
