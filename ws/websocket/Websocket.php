<?php

namespace ws\websocket;

class Websocket {

    private static ?Websocket $instance = null;

    private array $readSockets;

    private src\ServerConfig $serverConfig;
    private src\ClientManager $clientManager;
    private src\HandshakeHandler $handshakeHandler;
    private src\MessageHandler $messageHandler;
    private object $configs;
    private $server;

    private function __construct() {
        $this->configs = src\Constants::getConfigs();
        $this->setupServer();
        $this->setupAdditionals();
        $this->main();
    }

    private function setupServer() {
        $this->serverConfig = new src\ServerConfig(
            address: $this->configs->address, 
            port: $this->configs->port, 
            certFile: $this->configs->paths->cert, 
            keyFile: $this->configs->paths->key
        );

        src\Logger::checkPortUsage($this->serverConfig->getPort());

        $context = $this->serverConfig->getStreamContext();
        $address = $this->serverConfig->getAddress() . ':' . $this->serverConfig->getPort();
        
        $this->server = stream_socket_server('ssl://' . $address, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

        if (!$this->server) die("Error: $errstr ($errno)");

        stream_set_blocking($this->server, false);
    }

    private function setupAdditionals() {
        $this->clientManager    = new src\ClientManager($this->server);
        $this->handshakeHandler = new src\HandshakeHandler();
        $this->messageHandler   = new src\MessageHandler();
    }

    private function main() {
        while (true) {
            sleep(1);

            if ($this->validateReadSockets()) continue;
            $this->handleClients();
            $this->handleIO();
        }
    }

    private function validateReadSockets(): bool {
        $this->readSockets = array_merge([$this->clientManager->getServer()], $this->clientManager->getClients());
        $writeSockets = null;
        $exceptSockets = null;

        return stream_select($this->readSockets, $writeSockets, $exceptSockets, 0) <= 0;
    }

    private function handleClients() {
        if (!in_array($this->clientManager->getServer(), $this->readSockets)) return;

        $client = $this->clientManager->acceptClient();

        if ($client && !$this->handshakeHandler->performHandshake($client))
            $this->clientManager->removeClient($client);
    }
    
    private function handleIO(): void {
        foreach ($this->readSockets as $client) {
            $data = fread($client, 5000);

            if (!$data) continue;

            $encode = $this->messageHandler->handleMessage(client: $client, data: $data);
            $encode = json_decode($encode);

            if (!$encode) continue;

            switch ($encode->type) {
                case 'identifier':
                    if (isset($encode->id))
                        $this->clientManager->setClientByID($encode->id, $client);
                    break;
                case 'update':
                    if (isset($encode->clientID)) {
                        $message = 'update' . $encode->message;
                        $tmpClient = $this->getClientManager()->getClient($encode->clientID);
                        $this->messageTo($tmpClient, $message);
                    }
                    break;
                case 'getClients':
                    $this->messageTo($client, json_encode(array_keys($this->getClientManager()->getClients())));
                    break;
            }
        }
    }

    public function broadcast(array $clients) {
        $this->messageHandler->broadcastMessage($clients, 'Pong');
    }

    public function messageTo($client, string $message) {
        if (!$client) return;

        fwrite($client, chr(src\Constants::FINAL_TEXT_FRAME) . chr(strlen($message)) . $message);
    }

    public static function getInstance(): Websocket {
        if (!self::$instance) self::$instance = new Websocket();
        return self::$instance;
    }

    public function getClientManager(): src\ClientManager {
        return $this->clientManager;
    }

}
