<?php

namespace App\Http\Services\EisGov;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;

class GosplanInfo implements HttpInterface
{
    private array $data;
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => Config::get('eisgov.gosplan_info.base_uri_fz44')
        ]);
    }

    public function get(string $uri, array $options = []): array
    {
        $response = [];
        try {
            $response['data'] = json_decode(
                $this->client->request('GET', $uri, $options)->getBody()->getContents()
            );
        } catch (GuzzleException $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    public function post(string $uri, array $options = []): array
    {
        try {
            $data = json_decode(
                $this->client->request('POST', $uri, $options)->getBody()->getContents()
            );
        }
        catch (GuzzleException $e){
            $data = ['error' => $e->getMessage()];
        }

        return $data;
    }
}
