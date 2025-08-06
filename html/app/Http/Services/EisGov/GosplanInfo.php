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
    private array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->client = new Client([
            //'base_uri' => $this->baseUri,
            'timeout' => 30,
            'connect_timeout' => 15
        ]);
        $this->baseUri = '';
    }

    public function setBaseUri(): void
    {
        if (empty($this->params['typeFZ'])) {
            throw new RuntimeException('TypeFZ for Gosplan.info service is not configured');
        }
        if (str_contains($this->params['typeFZ'], '44')) {
            $this->baseUri = Config::get('eisgov.gosplan_info.base_uri_fz44');
        }
        if (str_contains($this->params['typeFZ'], '223')) {
            $this->baseUri = Config::get('eisgov.gosplan_info.base_uri_fz223');
        }
    }

    public function get(string $uri, array $options = []): array
    {
        $this->setBaseUri();
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
        $this->setBaseUri();
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
