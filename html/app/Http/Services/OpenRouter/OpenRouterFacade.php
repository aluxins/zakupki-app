<?php

namespace App\Http\Services\OpenRouter;

use Illuminate\Support\Facades\Facade;

/**
 * @method static OpenRouterResponseDTO chatCompletion(string $content, ?string $model = null, array $additionalOptions = [])
 * @method static array getModels()
 * @method static array getConfig()
 * 
 * @see \App\Http\Services\OpenRouter\OpenRouterService
 */
class OpenRouterFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'openrouter';
    }
}
