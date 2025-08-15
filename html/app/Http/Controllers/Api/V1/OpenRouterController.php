<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Services\OpenRouter\OpenRouterInterface;
use App\Http\Services\OpenRouter\OpenRouterRequestDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class OpenRouterController extends Controller
{
    public function __construct(
        private OpenRouterInterface $openRouterService
    ) {}

    /**
     * Send a chat completion request to OpenRouter.ai
     */
    public function chatCompletion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000000',
            'model' => 'nullable|string',
            'temperature' => 'nullable|numeric|between:0,2',
            'max_tokens' => 'nullable|integer|min:1|max:4000',
            'top_p' => 'nullable|numeric|between:0,1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $content = $request->input('content');
            $model = $request->input('model');

            $additionalOptions = array_filter([
                'temperature' => $request->input('temperature'),
                'max_tokens' => $request->input('max_tokens'),
                'top_p' => $request->input('top_p'),
            ]);

            $result = $this->openRouterService->chatCompletion($content, $model, $additionalOptions);

            if ($result['success']) {
                return ApiResponse::success('Chat completion successful', $result['data']);
            } else {
                return ApiResponse::error('Chat completion failed', $result['error'], $result['status_code']);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Internal server error', $e->getMessage(), 500);
        }
    }

    /**
     * Get available models from OpenRouter.ai
     */
    public function getModels(): JsonResponse
    {
        try {
            $result = $this->openRouterService->getModels();

            if ($result['success']) {
                return ApiResponse::success('Models retrieved successfully', $result['data']);
            } else {
                return ApiResponse::error('Failed to retrieve models', $result['error'], $result['status_code']);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Internal server error', $e->getMessage(), 500);
        }
    }

    /**
     * Get OpenRouter service configuration
     */
    public function getConfig(): JsonResponse
    {
        try {
            $config = $this->openRouterService->getConfig();

            // Remove sensitive information
            unset($config['api_token']);

            return ApiResponse::success('Configuration retrieved successfully', $config);

        } catch (\Exception $e) {
            return ApiResponse::error('Internal server error', $e->getMessage(), 500);
        }
    }

    /**
     * Test the OpenRouter service with a simple message
     */
    public function test(): JsonResponse
    {
        try {
            $testMessage = "Hello! This is a test message. Please respond with a simple greeting.";

            $result = $this->openRouterService->chatCompletion($testMessage);

            if ($result['success']) {
                return ApiResponse::success('Test successful', [
                    'test_message' => $testMessage,
                    'response' => $result['data']
                ]);
            } else {
                return ApiResponse::error('Test failed', $result['error'], $result['status_code']);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Test failed', $e->getMessage(), 500);
        }
    }
}
