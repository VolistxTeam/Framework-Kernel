<?php

namespace Volistx\FrameworkKernel\Helpers\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class RequestHelper
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function Get($url, $token, array $query = []): ProcessedResponse
    {
        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'query' => $query,
            ]);

            return new ProcessedResponse($response);
        } catch (ClientException|GuzzleException $ex) {
            return new ProcessedResponse($ex);
        }
    }

    public function Post($url, $token, array $query = []): ProcessedResponse
    {
        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'json' => $query,
            ]);

            return new ProcessedResponse($response);
        } catch (ClientException|GuzzleException $ex) {
            return new ProcessedResponse($ex);
        }
    }

    public function Put($url, $token, array $query = []): ProcessedResponse
    {
        try {
            $response = $this->client->request('PUT', $url, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'json' => $query,
            ]);

            return new ProcessedResponse($response);
        } catch (ClientException|GuzzleException $ex) {
            return new ProcessedResponse($ex);
        }
    }

    public function Patch($url, $token, array $query = []): ProcessedResponse
    {
        try {
            $response = $this->client->request('PATCH', $url, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'json' => $query,
            ]);

            return new ProcessedResponse($response);
        } catch (ClientException|GuzzleException $ex) {
            return new ProcessedResponse($ex);
        }
    }

    public function Delete($url, $token): ProcessedResponse
    {
        try {
            $response = $this->client->request('DELETE', $url, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
            ]);

            return new ProcessedResponse($response);
        } catch (ClientException|GuzzleException $ex) {
            return new ProcessedResponse($ex);
        }
    }
}
