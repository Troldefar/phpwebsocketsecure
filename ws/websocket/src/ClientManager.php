<?php

namespace ws\websocket\src;

class ClientManager {

    private $server;
    private array $clients = [];

    public function __construct($server) {
        $this->server = $server;
    }

    public function acceptClient() {
        $client = stream_socket_accept($this->server, 500);
        if (!$client) return;

        stream_set_blocking($client, false);
        $this->clients[] = $client;

        return $client;
    }

    public function setClientByID(string $id, $client) {
        $this->clients[$id] = $client;
    }

    public function removeClient($client) {
        fclose($client);
        unset($this->clients[array_search($client, $this->clients)]);
    }

    /**
     * @return [\resource]
     */

    public function getClients(): array {
        return $this->clients;
    }

    public function getServer() {
        return $this->server;
    }

    public function getClient(string $client): mixed {
        return $this->clients[$client] ?? null;
    }

    public function sendMessageToClient($client, $message): bool|int {
        if (!$this->getClient($client)) return false;

        return fwrite($client, $message);
    }

    /**
     * Enable if you want to remove passives
     */

    public function removePassiveClient($client, $data) {
        return;
        
        if ($data === false || strlen($data) === 0)
            $this->removeClient($client);
    }
}
