<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class CustomWebSocket {

    private int $port = 12345;
    private string $address = '0.0.0.0';
    private string $certFile = 'CERTIFICATE_PATH';
    private string $keyFile = 'PRIVATE_KEY_PATH';
    private string $SHA1_KEY = 'YOUR_SHA1_KEY';

    private const DEFAULT_FILE_READ_LENGTH = 5000;
    private const ERROR_WEBSOCKET_KEY = 'WebSocket key not found in the request' . PHP_EOL;
    private const CLIENT_CONNECTED = 'Client connected' . PHP_EOL;
    private const WRAPPER = 'ssl';

    private $server;
    private $clients = [];

    public function __construct() {
        $this->checkPortUsage();
        $this->setupContext();
        $this->mainLoop();
    }

    private function checkPortUsage() {
        $cmd = sprintf('lsof -i:%d -t', $this->port);
        $output = shell_exec($cmd);
        if (!$output) return;
        
        $pids = explode("\n", trim($output));
        foreach ($pids as $pid) {
            if (is_numeric($pid)) {
                echo "Killing process $pid using port {$this->port}\n";
                posix_kill((int)$pid, SIGTERM);
            }
        }
        sleep(1);
    }

    private function mainLoop() {
        while (true) {
            $readSockets = array_merge([$this->server], $this->clients);
            $writeSockets = null;
            $exceptSockets = null;

            if (stream_select($readSockets, $writeSockets, $exceptSockets, 0) > 0) {
                if (in_array($this->server, $readSockets)) {
                    $client = stream_socket_accept($this->server, 0);
                    if ($client) {
                        $this->performHandshake($client);
                        stream_set_blocking($client, false);
                        $this->clients[] = $client;
                        echo self::CLIENT_CONNECTED;
                    }
                    unset($readSockets[array_search($this->server, $readSockets)]);
                }

                foreach ($readSockets as $client) {
                    $data = fread($client, self::DEFAULT_FILE_READ_LENGTH);
                    if ($data === false || strlen($data) === 0) {
                        $this->disconnectClient($client);
                        continue;
                    }

                    $this->handleMessage($client, $data);
                }
            }

            $this->broadcastMessage("Now: " . time());
            sleep(1);
        }
    }

    private function performHandshake($client) {
        $request = fread($client, self::DEFAULT_FILE_READ_LENGTH);
        echo "Request received:\n$request\n";
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);

        if (!isset($matches[1])) {
            echo self::ERROR_WEBSOCKET_KEY;
            fclose($client);
            return;
        }

        $key = base64_encode(pack('H*', sha1($matches[1] . $this->SHA1_KEY)));

        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";

        fwrite($client, $headers, strlen($headers));
        echo "Handshake sent:\n$headers\n";
    }

    private function handleMessage($client, $data) {
        echo "Received from client: $data\n";
    }

    private function broadcastMessage($message) {
        $response = chr(129) . chr(strlen($message)) . $message;
        foreach ($this->clients as $client) {
            fwrite($client, $response);
            echo "Sent: $message\n";
        }
    }

    private function disconnectClient($client) {
        fclose($client);
        unset($this->clients[array_search($client, $this->clients)]);
        echo "Client disconnected\n";
    }

    private function getStreamContextOptions(): array {
        return [
            self::WRAPPER => [
                'local_cert' => $this->certFile,
                'local_pk' => $this->keyFile,
                'allow_self_signed' => true,
                'verify_peer' => false
            ]
        ];
    }

    private function setupContext() {
        $context = stream_context_create($this->getStreamContextOptions());

        stream_context_set_option($context, self::WRAPPER, 'crypto_method', STREAM_CRYPTO_METHOD_TLS_SERVER);
        stream_context_set_option($context, self::WRAPPER, 'capture_peer_cert', true);
        stream_context_set_option($context, self::WRAPPER, 'capture_peer_cert_chain', true);

        $this->server = stream_socket_server(
            self::WRAPPER."://$this->address:$this->port", 
            $errno, 
            $errstr, 
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, 
            $context
        );

        if (!$this->server) die("Error: $errstr ($errno)");

        stream_set_blocking($this->server, false);
        echo "Server started at $this->address:$this->port\n";
    }

}

new CustomWebSocket();
