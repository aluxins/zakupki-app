<?php

namespace App\MoonShine\Helpers;

use App\Http\Services\Favorites\FavoritesFacade;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Support\Enums\ToastType;
use MoonShine\UI\Fields\Switcher;

/**
 * Trait для добавления функциональности избранного в MoonShine ресурсы.
 *
 * Предоставляет методы для асинхронного переключения статуса избранного
 * и создания UI компонентов для управления избранным.
 *
 * @package App\MoonShine\Helpers
 * @version 1.0
 *
 *
 * @example
 * ```php
 * class PostResource extends ModelResource
 * {
 *     use ToggleFavorite;
 *
 *     public function fields(): array
 *     {
 *         return [
 *             // Для текущей модели
 *             $this->switcherFavorite(),
 *
 *             // Для связанной модели
 *             $this->switcherFavorite('relatedModel'),
 *
 *             // С кастомной папкой
 *             $this->switcherFavorite(null, 'Custom Folder', true),
 *         ];
 *     }
 * }
 * ```
 */
trait ToggleFavorite
{
    private array $foldersFavorite = [];
    /**
     * Асинхронное переключение статуса избранного для модели.
     *
     * @param MoonShineRequest $request
     * @return MoonShineJsonResponse
     */
    public function toggleFavorite(MoonShineRequest $request): MoonShineJsonResponse
    {
        try {
            // Валидация запроса
            if (!$this->validateToggleFavoriteRequest($request)) {
                return $this->createErrorResponse('Некорректные данные запроса');
            }

            // Получение модели
            $model = $this->getModelFromRequest($request);
            if (!$model) {
                return $this->createErrorResponse('Модель не найдена');
            }

            // Получение параметров папки
            $folderName = $request->input('folder_name', $model->getTable());
            $personal = $request->boolean('personal', false);

            // Переключение статуса избранного
            $isNowFavorite = FavoritesFacade::toggleFavorite(
                Auth::id(),
                $model,
                $folderName,
                $personal
            );

            return $this->createSuccessResponse($isNowFavorite);

        } catch (Exception $e) {
            Log::error('ToggleFavorite error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            return $this->createErrorResponse('Произошла ошибка при изменении статуса избранного');
        }
    }

    /**
     * Создает поле Switcher для управления избранным.
     *
     * @param string|null $modelName Имя связанной модели (null для текущей модели)
     * @param string|null $folderName Название папки для группировки
     * @param bool $personal Персональная папка
     * @return Switcher
     */
    public function switcherFavorite(
        ?string $modelName = null,
        ?string $folderName = null,
        bool $personal = false
    ): Switcher {
        return Switcher::make('Избранное')
            ->changePreview(function ($value, Switcher $context) use ($modelName, $folderName, $personal) {
                $model = $this->getModelForSwitcher($context, $modelName);

                if (!$model) {
                    return Switcher::make()->setValue(0);
                }

                $actualFolderName = $folderName ?? $model->getTable();
                $folderId = $this->getFolderId($actualFolderName, $personal);

                return Switcher::make()
                    ->setValue($this->getSetValue($model, $folderId, $actualFolderName, $personal))
                    ->updateOnPreview(
                        fn() => $this->buildToggleEndpoint($model, $actualFolderName, $personal)
                    );
            });
    }

    /**
     * Получение статуса избранного через полиморфное отношение модели или через фасад сервиса.
     *
     * @param $model
     * @param int $folderId
     * @param string $folderName
     * @param bool $personal
     * @return int
     */
    private function getSetValue($model, int $folderId, string $folderName, bool $personal): int
    {
        if (method_exists($model, 'favorite')) {
            return $model->favorite($folderId)->first() ? 1 : 0;
        } else {
            return (int)FavoritesFacade::isFavorite(
                Auth::id(),
                $model,
                $folderName,
                $personal
            );
        }
    }

    /**
     * Получение ID папки.
     *
     * @param string $folderName
     * @param bool $personal
     * @return int
     */
    private function getFolderId(string $folderName, bool $personal): int
    {
        $key = "{$folderName}_{$personal}_" . Auth::id();
        if(!array_key_exists($key, $this->foldersFavorite)) {
            $folder = FavoritesFacade::getFolderForName($folderName, $personal ? Auth::id() : null);
            $this->foldersFavorite[$key] = $folder->id ?? null;
        }
        return $this->foldersFavorite[$key];
    }

    /**
     * Валидация запроса на переключение избранного.
     *
     * @param MoonShineRequest $request
     * @return bool
     */
    private function validateToggleFavoriteRequest(MoonShineRequest $request): bool
    {
        $validator = Validator::make($request->all(), [
            'model_type' => [
                'required',
                'string',
                'regex:/^App\\\\Models\\\\[A-Za-z0-9_]+$/i'
            ],
            'model_id' => 'required|integer|min:1',
            'folder_name' => 'nullable|string|max:255',
            'personal' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            Log::warning('ToggleFavorite validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            return false;
        }

        // Проверка существования класса модели
        $modelType = $request->input('model_type');
        if (!class_exists($modelType) || !is_subclass_of($modelType, Model::class)) {
            Log::warning('ToggleFavorite invalid model class', [
                'model_type' => $modelType
            ]);
            return false;
        }

        return true;
    }

    /**
     * Получение модели из запроса.
     *
     * @param MoonShineRequest $request
     * @return Model|null
     */
    private function getModelFromRequest(MoonShineRequest $request): ?Model
    {
        try {
            $modelType = $request->input('model_type');
            $modelId = $request->input('model_id');

            return $modelType::findOrFail($modelId);
        } catch (Exception $e) {
            Log::error('Failed to find model in ToggleFavorite', [
                'model_type' => $request->input('model_type'),
                'model_id' => $request->input('model_id'),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Получение модели для Switcher компонента.
     *
     * @param Switcher $context
     * @param string|null $modelName
     * @return Model|null
     */
    private function getModelForSwitcher(Switcher $context, ?string $modelName): ?Model
    {
        try {
            if ($modelName) {
                // Для связанной модели
                return $context->getData()->getOriginal()->$modelName ?? null;
            }

            // Для текущей модели ресурса
            return $this->getItem();
        } catch (Exception $e) {
            Log::error('Failed to get model for switcher', [
                'model_name' => $modelName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Построение URL для асинхронного запроса.
     *
     * @param Model $model
     * @param string $folderName
     * @param bool $personal
     * @return string
     */
    private function buildToggleEndpoint(Model $model, string $folderName, bool $personal): string
    {
        return $this->getRouter()->getEndpoints()->method(
            'toggleFavorite',
            null,
            [
                'model_type' => get_class($model),
                'model_id' => $model->getAttributeValue('id'),
                'folder_name' => $folderName,
                'personal' => $personal
            ]
        );
    }

    /**
     * Создание успешного ответа.
     *
     * @param bool $isNowFavorite
     * @return MoonShineJsonResponse
     */
    private function createSuccessResponse(bool $isNowFavorite): MoonShineJsonResponse
    {
        $message = $isNowFavorite ? 'Добавлено в избранное' : 'Удалено из избранного';
        $type = ToastType::SUCCESS;

        return MoonShineJsonResponse::make()->toast($message, $type);
    }

    /**
     * Создание ответа об ошибке.
     *
     * @param string $message
     * @return MoonShineJsonResponse
     */
    private function createErrorResponse(string $message): MoonShineJsonResponse
    {
        return MoonShineJsonResponse::make()->toast($message, ToastType::ERROR);
    }
}
