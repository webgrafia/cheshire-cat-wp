<?php

namespace CheshireCatWp\classes;

use CheshireCatSdk\CheshireCat;
class CustomCheshireCat extends CheshireCat
{
    protected $baseUrl;
    protected $token;
    protected $client;

    public function __construct($baseUrl, $token)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
        //parent::__construct(); // Remove this line
        $this->client = $this->createClient();
    }

    protected function createClient()
    {
        return new CustomCheshireCatClient($this->baseUrl, $this->token);
    }
    public function sendMessage(string $message, array $options = []): array
    {
        $payload = [
            'text' => $message,
        ];
        try {
            $response = $this->client->sendMessage($payload);
            if (is_null($response)) {
                return [];
            }
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return [];
        }
    }
    public function getStatus() {
        try {
            $response = $this->client->getStatus();
            if (is_null($response)) {
                return [];
            }
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getAvailablePlugins() {
        try {
            $response = $this->client->getAvailablePlugins();
            if (is_null($response)) {
                return [];
            }
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return [];
        }

    }
}