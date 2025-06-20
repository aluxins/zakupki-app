<?php

declare(strict_types=1);

namespace App\Http\Services\EisGov;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Throwable;

class GosplanInfo implements HttpInterface
{
    private Client $client;
    private string $baseUri;

    public function __construct()
    {
        $this->client = new Client([
            //'base_uri' => $this->baseUri,
            'timeout' => 30,
            'connect_timeout' => 15
        ]);
    }

    public function setBaseUri(string $uri): void
    {
        if (empty($uri)) {
            throw new RuntimeException('Base URI for GosplanInfo service is not configured');
        }
        $this->baseUri = $uri;
    }

    public function get(string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request('GET', $this->baseUri . $uri, $options);
            $contents = $response->getBody()->getContents();

            $data = json_decode($contents, false);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to decode JSON response: ' . json_last_error_msg());
            }

            return ['data' => $data];

        } catch (GuzzleException $e) {
            return ['error' => sprintf(
                'HTTP request failed: %s (URI: %s)',
                $e->getMessage(),
                $uri
            )];
        } catch (Throwable $e) {
            return ['error' => sprintf(
                'Unexpected error: %s (URI: %s)',
                $e->getMessage(),
                $uri
            )];
        }
    }

    public function post(string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request('POST', $this->baseUri . $uri, $options);
            $contents = $response->getBody()->getContents();

            $data = json_decode($contents, false);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to decode JSON response: ' . json_last_error_msg());
            }

            return ['data' => $data];

        } catch (GuzzleException $e) {
            return ['error' => sprintf(
                'HTTP request failed: %s (URI: %s)',
                $e->getMessage(),
                $uri
            )];
        } catch (Throwable $e) {
            return ['error' => sprintf(
                'Unexpected error: %s (URI: %s)',
                $e->getMessage(),
                $uri
            )];
        }
    }
}
