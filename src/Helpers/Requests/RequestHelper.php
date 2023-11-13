<?php

namespace Volistx\FrameworkKernel\Helpers\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class RequestHelper
{
    private Client $client;

    /**
     * RequestHelper constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Sends a GET request.
     *
     * @param string $url    The URL to send the request to
     * @param string $token  The authorization token
     * @param array  $query  The query parameters
     *
     * @return ProcessedResponse The processed response
     */
    public function get(string $url, string $token, array $query = []): ProcessedResponse
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

    /**
     * Sends a POST request.
     *
     * @param string $url    The URL to send the request to
     * @param string $token  The authorization token
     * @param array  $query  The request body
     *
     * @return ProcessedResponse The processed response
     */
    public function post(string $url, string $token, array $query = []): ProcessedResponse
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

    /**
     * Sends a PUT request.
     *
     * @param string $url    The URL to send the request to
     * @param string $token  The authorization token
     * @param array  $query  The request body
     *
     * @return ProcessedResponse The processed response
     */
    public function put(string $url, string $token, array $query = []): ProcessedResponse
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

    /**
     * Sends a PATCH request.
     *
     * @param string $url    The URL to send the request to
     * @param string $token  The authorization token
     * @param array  $query  The request body
     *
     * @return ProcessedResponse The processed response
     */
    public function patch(string $url, string $token, array $query = []): ProcessedResponse
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

    /**
     * Sends a DELETE request.
     *
     * @param string $url    The URL to send the request to
     * @param string $token  The authorization token
     *
     * @return ProcessedResponse The processed response
     */
    public function delete(string $url, string $token): ProcessedResponse
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