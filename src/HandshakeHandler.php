<?php

class HandshakeHandler {
    private string $SHA1_KEY = 'YOUR_SHA1_KEY';

    public function performHandshake($client) {
        $request = fread($client, 5000);
        echo "Request received:\n$request\n";

        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);

        if (!isset($matches[1])) {
            echo 'WebSocket key not found in the request' . PHP_EOL;
            fclose($client);
            return false;
        }

        $key = base64_encode(pack('H*', sha1($matches[1] . $this->SHA1_KEY)));

        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";

        fwrite($client, $headers, strlen($headers));
        echo "Handshake sent:\n$headers\n";
        return true;
    }
}
