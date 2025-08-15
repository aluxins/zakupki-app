<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Services\OpenRouter\OpenRouterInterface;
use App\Http\Services\OpenRouter\OpenRouterRequestDTO;
use App\Http\Services\OpenRouter\OpenRouterResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Services\OpenRouter\OpenRouterFacade;

class OpenRouterExampleController extends Controller
{
    public function __construct(
        private OpenRouterInterface $openRouterService
    ) {}

    /**
     * Пример использования сервиса напрямую
     */
    public function directUsage(): JsonResponse
    {
        try {
            $result = $this->openRouterService->chatCompletion(
                'Напиши короткое стихотворение о программировании'
            );

            if ($result['success']) {
                return ApiResponse::success('Прямое использование успешно',
                    [
                        'content' => $result['data']['choices'][0]['message']['content'],
                        'model' => $result['data']['model'],
                        'usage' => $result['data']['usage'] ?? null
                    ]);
            } else {
                return ApiResponse::error('Ошибка при прямом использовании', $result['error']);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Внутренняя ошибка сервера', $e->getMessage());
        }
    }

    /**
     * Пример использования с DTO
     */
    public function dtoUsage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
                'temperature' => 'nullable|numeric|between:0,2',
                'max_tokens' => 'nullable|integer|min:10|max:1000'
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Ошибка валидации', $validator->errors(), 422);
            }

            $requestDTO = OpenRouterRequestDTO::createChatRequest(
                $request->input('content'),
                config('openrouter.default_model'),
                [
                    'temperature' => $request->input('temperature', 0.7),
                    'max_tokens' => $request->input('max_tokens', 200)
                ]
            );

            $result = $this->openRouterService->chatCompletion(
                $requestDTO->messages[0]['content'],
                $requestDTO->model,
                [
                    'temperature' => $requestDTO->temperature,
                    'max_tokens' => $requestDTO->maxTokens
                ]
            );

            if ($result['success']) {
                $responseDTO = OpenRouterResponseDTO::fromArray($result);

                return ApiResponse::success(
                    'Использование с DTO успешно',
                    [
                        'content' => $responseDTO->getContent(),
                        'model' => $responseDTO->getModel(),
                        'usage' => $responseDTO->getUsage(),
                        'request_params' => [
                            'temperature' => $requestDTO->temperature,
                            'max_tokens' => $requestDTO->maxTokens
                        ]
                    ]
                );
            } else {
                return ApiResponse::error('Ошибка при использовании с DTO', $result['error']);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Внутренняя ошибка сервера', $e->getMessage());
        }
    }

    /**
     * Пример использования с кастомной моделью
     */
    public function customModelUsage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
                'model' => 'required|string'
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Ошибка валидации', $validator->errors(), 422);
            }

            $result = $this->openRouterService->chatCompletion(
                $request->input('content'),
                $request->input('model')
            );

            if ($result['success']) {
                return ApiResponse::success(
                    'Использование с кастомной моделью успешно',
                    [
                        'content' => $result['data']['choices'][0]['message']['content'],
                        'model' => $result['data']['model'],
                        'usage' => $result['data']['usage'] ?? null
                    ]
                );
            } else {
                return ApiResponse::error('Ошибка при использовании с кастомной моделью', $result['error']);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Внутренняя ошибка сервера', $e->getMessage());
        }
    }

    /**
     * Пример использования с дополнительными параметрами
     */
    public function advancedUsage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
                'temperature' => 'nullable|numeric|between:0,2',
                'max_tokens' => 'nullable|integer|min:10|max:4000',
                'top_p' => 'nullable|numeric|between:0,1',
                'presence_penalty' => 'nullable|numeric|between:-2,2',
                'frequency_penalty' => 'nullable|numeric|between:-2,2'
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Ошибка валидации', $validator->errors(), 422);
            }

            $additionalOptions = array_filter([
                'temperature' => $request->input('temperature'),
                'max_tokens' => $request->input('max_tokens'),
                'top_p' => $request->input('top_p'),
                'presence_penalty' => $request->input('presence_penalty'),
                'frequency_penalty' => $request->input('frequency_penalty')
            ]);

            $result = $this->openRouterService->chatCompletion(
                $request->input('content'),
                null, // Используем модель по умолчанию
                $additionalOptions
            );

            if ($result['success']) {
                return ApiResponse::success(
                    'Расширенное использование успешно',
                    [
                        'content' => $result['data']['choices'][0]['message']['content'],
                        'model' => $result['data']['model'],
                        'usage' => $result['data']['usage'] ?? null,
                        'used_parameters' => $additionalOptions
                    ]
                );
            } else {
                return ApiResponse::error('Ошибка при расширенном использовании', $result['error']);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Внутренняя ошибка сервера', $e->getMessage());
        }
    }

    /**
     * Пример получения информации о доступных моделях
     */
    public function modelsInfo(): JsonResponse
    {
        try {
            $result = $this->openRouterService->getModels();

            if ($result['success']) {
                $models = collect($result['data']['data'] ?? [])
                    ->map(function ($model) {
                        return [
                            'id' => $model['id'] ?? '',
                            'name' => $model['name'] ?? '',
                            'description' => $model['description'] ?? '',
                            'pricing' => $model['pricing'] ?? null,
                            'context_length' => $model['context_length'] ?? null
                        ];
                    })
                    ->filter()
                    ->values();

                return ApiResponse::success(
                    'Информация о моделях получена',
                    [
                        'total_models' => $models->count(),
                        'models' => $models->take(10)->toArray(), // Показываем только первые 10
                        'note' => 'Показаны только первые 10 моделей из общего количества'
                    ]
                );
            } else {
                return ApiResponse::error('Ошибка при получении моделей', $result['error']);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Внутренняя ошибка сервера', $e->getMessage());
        }
    }

    public function facadeUsage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Ошибка валидации', $validator->errors(), 422);
            }
            $result = OpenRouterFacade::chatCompletion($request->input('content'));

            if ($result['success']) {
                return ApiResponse::success('Успешное использование фасада',
                    [
                        'content' => $result['data']['choices'][0]['message']['content'],
                        'model' => $result['data']['model'],
                        'usage' => $result['data']['usage'] ?? null
                    ]);
            } else {
                return ApiResponse::error('Ошибка при использовании фасада', $result['error']);
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Внутренняя ошибка сервера', $e->getMessage());
        }
    }
}
