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

    public function __construct()
    {
        $baseUri = Config::get('eisgov.gosplan_info.base_uri_fz44');

        if (empty($baseUri)) {
            throw new RuntimeException('Base URI for GosplanInfo service is not configured');
        }

        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout' => 30,
            'connect_timeout' => 15
        ]);
    }

    public function get(string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request('GET', $uri, $options);
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
            $response = $this->client->request('POST', $uri, $options);
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
