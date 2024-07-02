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
        fwrite($client, FrameHandler::encodeWebSocketFrame($message));
    }
}