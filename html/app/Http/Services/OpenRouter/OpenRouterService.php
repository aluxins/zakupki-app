<?php

namespace App\Http\Services\OpenRouter;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenRouterService implements OpenRouterInterface
{
    private Client $client;
    private string $apiUrl;
    private string $chatEndpoint;
    private string $apiToken;
    private string $defaultModel;
    private int $timeout;
    private int $maxRetries;

    public function __construct()
    {
        $this->apiUrl = Config::get('openrouter.api_url');
        $this->chatEndpoint = Config::get('openrouter.chat_endpoint');
        $this->apiToken = Config::get('openrouter.api_token');
        $this->defaultModel = Config::get('openrouter.default_model');
        $this->timeout = Config::get('openrouter.timeout');
        $this->maxRetries = Config::get('openrouter.max_retries');

        if (empty($this->apiToken)) {
            throw new RuntimeException('OpenRouter API token is not configured');
        }

        $this->client = new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-OpenRouter-Service/1.0'
            ]
        ]);
    }

    /**
     * Send a chat completion request to OpenRouter.ai API
     *
     * @param string $content The user message content
     * @param string|null $model The AI model to use (optional, uses default if not specified)
     * @param array $additionalOptions Additional options for the request
     * @return array The API response
     */
    public function chatCompletion(string $content, ?string $model = null, array $additionalOptions = []): array
    {
        $model = $model ?: $this->defaultModel;

        $requestData = array_merge([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content
                ]
            ]
        ], $additionalOptions);

        try {
            $response = $this->client->post($this->apiUrl . $this->chatEndpoint, [
                'json' => $requestData
            ]);

            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Invalid JSON response from OpenRouter API');
            }

            return [
                'success' => true,
                'data' => $responseData,
                'status_code' => $response->getStatusCode()
            ];

        } catch (GuzzleException $e) {
            Log::error('OpenRouter API request failed', [
                'error' => $e->getMessage(),
                'content' => mb_substr($content, 0, 256),
                'model' => $model
            ]);

            return [
                'success' => false,
                'error' => 'HTTP request failed: ' . $e->getMessage(),
                'status_code' => $e->getCode() ?: 500
            ];

        } catch (Exception $e) {
            Log::error('OpenRouter service error', [
                'error' => $e->getMessage(),
                'content' => mb_substr($content, 0, 256),
                'model' => $model
            ]);

            return [
                'success' => false,
                'error' => 'Service error: ' . $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Get available models from OpenRouter.ai
     *
     * @return array The list of available models
     */
    public function getModels(): array
    {
        try {
            $response = $this->client->get($this->apiUrl . '/models');
            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Invalid JSON response from OpenRouter API');
            }

            return [
                'success' => true,
                'data' => $responseData,
                'status_code' => $response->getStatusCode()
            ];

        } catch (GuzzleException $e) {
            Log::error('OpenRouter models request failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'HTTP request failed: ' . $e->getMessage(),
                'status_code' => $e->getCode() ?: 500
            ];

        } catch (Exception $e) {
            Log::error('OpenRouter models service error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Service error: ' . $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Get the current configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'api_url' => $this->apiUrl,
            'chat_endpoint' => $this->chatEndpoint,
            'default_model' => $this->defaultModel,
            'timeout' => $this->timeout,
            'max_retries' => $this->maxRetries
        ];
    }
}
