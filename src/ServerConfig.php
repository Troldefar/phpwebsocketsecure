<?php

namespace app\core\src\websocket;

class ServerConfig {
    
    private const WRAPPER = 'ssl';

    public function __construct(
        private string $address, 
        private int $port, 
        private string $certFile, 
        private string $keyFile
    ) {
    }

    public function getStreamContext() {
        $context = stream_context_create([
            self::WRAPPER => [
                'local_cert' => $this->certFile,
                'local_pk' => $this->keyFile,
                'allow_self_signed' => false,
                'verify_peer' => false,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_SERVER,
                'capture_peer_cert' => true,
                'capture_peer_cert_chain' => true
            ]
        ]);

        return $context;
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function getPort(): int {
        return $this->port;
    }
}