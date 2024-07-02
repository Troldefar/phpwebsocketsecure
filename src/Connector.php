<?php

class Connector {

    public static function sendToServer($message = '123456') {
        $client = self::tryConnect();
        if (!$client) return;

        self::sendWebSocketMessage($client, $message);

        fclose($client);
    }

    private static function tryConnect(): mixed {
        $websocketConfigs = app()->getConfig()->get('integrations')->websocket;

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
            return false;
        }

        if (!self::performHandshake($client)) {
            fclose($client);
            return false;
        }

        return $client;
    }

    private static function performHandshake($client) {
        $websocketConfigs = app()->getConfig()->get('integrations')->websocket;
        $key = base64_encode($websocketConfigs->sha1key);

        fwrite($client, (new HandshakeHandler())->prepareBackendClientHeaders($key, $websocketConfigs));

        $response = fread($client, 1024);

        if (!preg_match('#Sec-WebSocket-Accept:\s(.*)$#mUsi', $response, $matches)) {
            app()->getResponse()->unauthorized("Invalid WebSocket handshake response:\n$response\n");
            return false;
        }

        return true;
    }

    private static function sendWebSocketMessage($client, $message) {
        fwrite($client, self::encodeWebSocketFrame($message));
    }

    private static function encodeWebSocketFrame($message) {
        $length = strlen($message);
        $frame = chr(129);

        if ($length <= 125) {
            $frame .= chr(0x80 | $length);
        } elseif ($length <= 65535) {
            $frame .= chr(0x80 | 126) . pack('n', $length);
        } else {
            $frame .= chr(0x80 | 127) . pack('J', 0, $length);
        }

        $mask = [];

        for ($i = 0; $i < 4; $i++)
            $mask[] = mt_rand(0, 255);

        $frame .= implode(array_map('chr', $mask));

        for ($i = 0; $i < $length; $i++)
            $frame .= chr(ord($message[$i]) ^ $mask[$i % 4]);

        return $frame;
    }
}