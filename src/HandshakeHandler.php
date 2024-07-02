<?php

namespace app\core\src\websocket;

class HandshakeHandler {
    
    public function prepareHeaders(string $key): string {
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";

        return $headers;
    }

    public function performHandshake($client) {
        $request = fread($client, 5000);
        Logger::yell("Request received:\n$request\n");

        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);

        if (!isset($matches[1])) {
            Logger::yell($request);
            Logger::yell('WebSocket key not found in the request' . PHP_EOL);
            return false;
        }

        $key = base64_encode(pack('H*', sha1($matches[1] . app()->getConfig()->get('integrations')->websocket->sha1key)));

        $headers = $this->prepareHeaders($key);

        fwrite($client, $headers, strlen($headers));

        Logger::yell("Handshake sent:\n$headers\n");

        return true;
    }
}