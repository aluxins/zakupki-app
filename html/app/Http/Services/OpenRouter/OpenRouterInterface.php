<?php

namespace App\Http\Services\OpenRouter;

interface OpenRouterInterface
{
    /**
     * Send a chat completion request to OpenRouter.ai API
     *
     * @param string $content The user message content
     * @param string|null $model The AI model to use (optional, uses default if not specified)
     * @param array $additionalOptions Additional options for the request
     * @return array The API response
     */
    public function chatCompletion(string $content, ?string $model = null, array $additionalOptions = []): array;
    
    /**
     * Get available models from OpenRouter.ai
     *
     * @return array The list of available models
     */
    public function getModels(): array;
}
