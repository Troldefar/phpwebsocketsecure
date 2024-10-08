<?php

namespace ws\websocket\src;

class ServerConfig {
    
    private const WRAPPER = 'ssl';

    public function __construct(
        private string $address, 
        private int $port, 
        private string $certFile, 
        private string $keyFile
    ) {
        
    }

    private function getDefaultPairs(): array {
        return [
            'local_cert' => $this->certFile,
            'local_pk' => $this->keyFile,
            'allow_self_signed' => false,
            'verify_peer' => false,
        ];
    }

    public function getStreamContext() {
        return stream_context_create([
            self::WRAPPER => [
                ...$this->getDefaultPairs(),
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_SERVER,
                'capture_peer_cert' => true,
                'capture_peer_cert_chain' => true
            ]
        ]);
    }

    public function getBackendClientStreamContext() {
        return stream_context_create([
            self::WRAPPER => [
                ...$this->getDefaultPairs(),
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
            ]
        ]);
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function getPort(): int {
        return $this->port;
    }
}
