<?php

namespace Volistx\FrameworkKernel\Helpers\Requests;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class ProcessedResponse
{
    public ?int $status_code;
    public ?array $headers;
    public mixed $body;
    public bool $isError;

    public function __construct($response)
    {
        if ($response instanceof (BadResponseException::class)) {
            $this->headers = $response->getResponse()->getHeaders();
            $this->body = json_decode($response->getResponse()->getBody()->getContents(), true);
            $this->status_code = $response->getResponse()->getStatusCode();
            $this->isError =  $this->status_code !== 200;

            return;
        }

        if ($response instanceof (ResponseInterface::class)) {
            $this->headers = $response->getHeaders();
            $this->body = json_decode($response->getBody()->getContents(), true);
            $this->status_code = $response->getStatusCode();
            $this->isError =  $this->status_code !== 200;
            return;
        }

        if ($response instanceof (GuzzleException::class)) {
            $this->status_code = 500;
            $this->isError =  $this->status_code !== 200;
            $this->headers = null;
            $this->body = null;
        }
    }
}
