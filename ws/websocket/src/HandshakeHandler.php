<?php

namespace app\core\src\websocket\src;

class HandshakeHandler {
    
    public function prepareHeaders(string $key): string {
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";

        return $headers;
    }

    public function prepareBackendClientHeaders(string $key, object $websocketConfigs): string {
        $request = "GET / HTTP/1.1\r\n";
        $request .= "Host: {$websocketConfigs->address}:{$websocketConfigs->port}\r\n";
        $request .= "Upgrade: websocket\r\n";
        $request .= "Connection: Upgrade\r\n";
        $request .= "Sec-WebSocket-Key: $key\r\n";
        $request .= "Sec-WebSocket-Version: 13\r\n";
        $request .= "\r\n";

        return $request;
    }

    /**
     * Max attempts in order to try the same client multiple times
     * Cases was found where the socket wouldnt get a proper response because of a ️🏁 condition
     */

    public function performHandshake($client) {

        $attempts = 5;
        $currentAttempt = 0;
        $request = '';

        while ($currentAttempt < $attempts) {
            $request = fread($client, 5000);
            // Logger::yell("Request received:\n$request\n");
            if ($request) break;
            usleep(100000);
            $attempts++;
        }

        if (!$request) return;

        preg_match(Constants::WEBSOCKET_HEADER_KEY, $request, $matches);

        if (!isset($matches[1])) {
            Logger::yell($request);
            Logger::yell(Constants::WEBSOCKET_HEADER_KEY_NOT_FOUND);
            return false;
        }

        $key = base64_encode(
            pack(
                Constants::PACK_FORMAT_ARG_HEX_ENTIRE_STRING, 
                sha1($matches[1] . Constants::getConfigs()->sha1key)
            )
        );

        $headers = $this->prepareHeaders($key);

        fwrite($client, $headers, strlen($headers));

        // Logger::yell("Handshake sent:\n$headers\n");

        return true;
    }
}