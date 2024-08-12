<?php

namespace ws\websocket\src;

class Connector {

    private static $client;

    private const WAIT_FOR_MESSAGE_KEY = 'wait';

    public static function sendToServer(mixed $message = Constants::DEFAULT_CLIENT_MESSAGE) {
        self::$client = self::tryConnect();
        if (!self::$client) return;

        self::sendWebSocketMessage($message);

        if (str_contains($message, self::WAIT_FOR_MESSAGE_KEY)) return self::waitForMessage();

        fclose(self::$client);
    }

    private static function waitForMessage() {
        return self::waitForResponse();
    }

    /**
     * In case you want something back
     */

    private static function waitForResponse() {
        $startTime = time();
        $timeout = 10;
        $buffer = '';

        while (time() - $startTime < $timeout) {
            $data = self::getWebsocketMessage();

            if ($data) {
                $buffer .= $data;
                break;
            }

            usleep(500000);
        }

        return $buffer;
    }

    private static function tryConnect(): mixed {
        try {
            $websocketConfigs = Constants::getConfigs();

            $serverConfig = new ServerConfig(
                address: $websocketConfigs->address, 
                port: $websocketConfigs->port, 
                certFile: $websocketConfigs->paths->cert,
                keyFile: $websocketConfigs->paths->key
            );

            $context = $serverConfig->getBackendClientStreamContext();
            $address = $serverConfig->getAddress() . ':' . $serverConfig->getPort();

            self::$client = @stream_socket_client('ssl://' . $address, $errno, $errstr, null, STREAM_CLIENT_CONNECT, $context);

            if (!self::$client) {
                app()->getResponse()->ok("Failed to connect: $errstr ($errno)\n");
                return false;
            }

            if (!self::performHandshake()) {
                fclose(self::$client);
                return false;
            }

            return self::$client;
        } catch (\Throwable $exception) {
            var_dump($exception->getMessage());
        }
    }

    private static function performHandshake() {
        $websocketConfigs = Constants::getConfigs();

        $key = base64_encode(openssl_random_pseudo_bytes(16));

        $handshaker = new HandshakeHandler();
        $headers = $handshaker->prepareBackendClientHeaders($key, $websocketConfigs);

        fwrite(self::$client, $headers);
        $response = fread(self::$client, 5000);

        if (preg_match(Constants::ACK_RESPONSE, $response, $matches)) return true;
        
        Logger::yell(Constants::INVALID_HANDSHAKE_RESPONSE . $response);
        return false;
    }

    private static function sendWebSocketMessage($message) {
        fwrite(self::$client, FrameHandler::encodeWebSocketFrame($message));
    }

    private static function getWebsocketMessage() {
        return fread(self::$client, 5000);
    }

}