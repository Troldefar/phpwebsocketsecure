<?php

/**
 * Signal dispatcher
 */

namespace ws\websocket\src;

class Connector {

    public static function sendToServer(mixed $message = Constants::DEFAULT_CLIENT_MESSAGE) {
        $client = self::tryConnect();
        if (!$client) return;

        self::sendWebSocketMessage($client, $message);

        fclose($client);
    }

    private static function tryConnect(): mixed {

        $websocketConfigs = Constants::getConfigs();

        $serverConfig = new ServerConfig(
            address: $websocketConfigs->address, 
            port: $websocketConfigs->port, 
            certFile: $websocketConfigs->paths->cert,
            keyFile: $websocketConfigs->paths->key
        );

        $context = $serverConfig->getBackendClientStreamContext();
        $address = $serverConfig->getAddress() . ':' . $serverConfig->getPort();

        $client = stream_socket_client('ssl://' . $address, $errno, $errstr, null, STREAM_CLIENT_CONNECT, $context);

        if (!$client) {
            app()->getResponse()->ok("Failed to connect: $errstr ($errno)\n");
            return false;
        }

        if (!self::performHandshake($client)) {
            fclose($client);
            return false;
        }

        return $client;
    }

    private static function performHandshake($client) {
        $websocketConfigs = Constants::getConfigs();

        $key = base64_encode(openssl_random_pseudo_bytes(16));

        $handshaker = new HandshakeHandler();
        $headers = $handshaker->prepareBackendClientHeaders($key, $websocketConfigs);

        fwrite($client, $headers);
        $response = fread($client, 5000);

        if (preg_match(Constants::ACK_RESPONSE, $response, $matches)) return true;
        
        Logger::yell(Constants::INVALID_HANDSHAKE_RESPONSE . $response);
        return false;
    }

    private static function sendWebSocketMessage($client, $message) {
        fwrite($client, FrameHandler::encodeWebSocketFrame($message));
    }

}
