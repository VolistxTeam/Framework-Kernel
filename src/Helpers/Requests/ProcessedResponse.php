<?php

namespace Volistx\FrameworkKernel\Helpers\Requests;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class ProcessedResponse
{
    public ?int $statusCode;
    public ?array $headers;
    public mixed $body;
    public bool $isError;

    /**
     * ProcessedResponse constructor.
     *
     * @param mixed $response The response object
     */
    public function __construct(mixed $response)
    {
        if ($response instanceof BadResponseException) {
            $this->headers = $response->getResponse()->getHeaders();
            $this->body = json_decode($response->getResponse()->getBody()->getContents(), true);
            $this->statusCode = $response->getResponse()->getStatusCode();
            $this->isError = $this->statusCode !== 200;
            return;
        }

        if ($response instanceof ResponseInterface) {
            $this->headers = $response->getHeaders();
            $this->body = json_decode($response->getBody()->getContents(), true);
            $this->statusCode = $response->getStatusCode();
            $this->isError = $this->statusCode !== 200;
            return;
        }

        if ($response instanceof GuzzleException) {
            $this->statusCode = 500;
            $this->isError = $this->statusCode !== 200;
            $this->headers = null;
            $this->body = null;
        }
    }
}