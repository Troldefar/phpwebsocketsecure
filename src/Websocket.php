<?php

class Websocket {

    private static ?Websocket $instance = null;

    private ServerConfig $serverConfig;
    private ClientManager $clientManager;
    private HandshakeHandler $handshakeHandler;
    private MessageHandler $messageHandler;
    private $server;

    public function __construct() {
        $this->setupServer();

        Logger::yell("Server started at {$this->serverConfig->getAddress()}:{$this->serverConfig->getPort()}\n");

        $this->setupAdditionals();
        $this->main();
    }

    private function setupServer() {
        $websocketConfigs = yourWayOfGettingStuff();

        $this->serverConfig = new ServerConfig(address: '0.0.0.0', port: 12345, certFile: $websocketConfigs->cert, keyFile: $websocketConfigs->key);

        Logger::checkPortUsage($this->serverConfig->getPort());

        $context = $this->serverConfig->getStreamContext();
        $address = $this->serverConfig->getAddress() . ':' . $this->serverConfig->getPort();
        
        $this->server = stream_socket_server('ssl://' . $address, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

        if (!$this->server) die("Error: $errstr ($errno)");

        stream_set_blocking($this->server, false);
    }

    private function setupAdditionals() {
        $this->clientManager    = new ClientManager($this->server);
        $this->handshakeHandler = new HandshakeHandler();
        $this->messageHandler   = new MessageHandler();
    }

    private function main() {
        while (true) {
            sleep(1);
            $readSockets = array_merge([$this->clientManager->getServer()], $this->clientManager->getClients());
            $writeSockets = null;
            $exceptSockets = null;

            if (stream_select($readSockets, $writeSockets, $exceptSockets, 0) > 0) {
                if (in_array($this->clientManager->getServer(), $readSockets)) {
                    $client = $this->clientManager->acceptClient();

                    if ($client && !$this->handshakeHandler->performHandshake($client))
                        $this->clientManager->removeClient($client);
                }

                foreach ($readSockets as $client) {
                    $data = fread($client, 5000);
                    $this->clientManager->removePassiveClient($client, $data);
                    $this->messageHandler->handleMessage($client, $data);
                }
            }

            $this->messageHandler->broadcastMessage($this->clientManager->getClients(), "Now: " . time());
        }
    }

    public static function getInstance() {
        if (!self::$instance) self::$instance = new Websocket();
        return self::$instance;
    }

    public function getClientManager(): ClientManager {
        return $this->clientManager;
    }

}
