<?php

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
            ]
        ]);

        stream_context_set_option($context, self::WRAPPER, 'crypto_method', STREAM_CRYPTO_METHOD_TLS_SERVER);
        stream_context_set_option($context, self::WRAPPER, 'capture_peer_cert', true);
        stream_context_set_option($context, self::WRAPPER, 'capture_peer_cert_chain', true);

        return $context;
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function getPort(): int {
        return $this->port;
    }
}
