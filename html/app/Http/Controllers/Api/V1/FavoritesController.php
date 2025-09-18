<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Services\Favorites\FavoritesFacade;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FavoritesController extends Controller
{
    /**
     * Add an item to favorites
     */
    public function addToFavorites(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|integer|exists:moonshine_users,id',
            'model_type' => 'required|string|in:App\Models\Purchase,App\Models\File',
            'model_id' => 'required|integer',
            'folder_name' => 'nullable|string|max:255',
            'personal' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $modelClass = $request->input('model_type');
            $model = $modelClass::findOrFail($request->input('model_id'));

            $favorite = FavoritesFacade::addToFavorites(
                $request->input('owner_id'),
                $model,
                $request->input('folder_name'),
                $request->boolean('personal', false)
            );

            return ApiResponse::success('Item added to favorites', [
                'favorite' => $favorite,
                'folder_name' => $request->input('folder_name')
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to add to favorites', $e->getMessage(), 500);
        }
    }

    /**
     * Remove an item from favorites
     */
    public function removeFromFavorites(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|integer|exists:moonshine_users,id',
            'model_type' => 'required|string|in:App\Models\Purchase,App\Models\File',
            'model_id' => 'required|integer',
            'folder_name' => 'nullable|string|max:255',
            'personal' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $modelClass = $request->input('model_type');
            $model = $modelClass::findOrFail($request->input('model_id'));

            $result = FavoritesFacade::removeFromFavorites(
                $request->input('owner_id'),
                $model,
                $request->input('folder_name'),
                $request->boolean('personal', false)
            );

            if ($result) {
                return ApiResponse::success('Item removed from favorites');
            } else {
                return ApiResponse::error('Item was not found in favorites', null, 404);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to remove from favorites', $e->getMessage(), 500);
        }
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|integer|exists:moonshine_users,id',
            'model_type' => 'required|string|in:App\Models\Purchase,App\Models\File',
            'model_id' => 'required|integer',
            'folder_name' => 'nullable|string|max:255',
            'personal' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $modelClass = $request->input('model_type');
            $model = $modelClass::findOrFail($request->input('model_id'));

            $isNowFavorite = FavoritesFacade::toggleFavorite(
                $request->input('owner_id'),
                $model,
                $request->input('folder_name'),
                $request->boolean('personal', false)
            );

            return ApiResponse::success('Favorite status toggled', [
                'is_favorite' => $isNowFavorite,
                'action' => $isNowFavorite ? 'added' : 'removed'
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to toggle favorite', $e->getMessage(), 500);
        }
    }

    /**
     * Check if item is favorite
     */
    public function isFavorite(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|integer|exists:moonshine_users,id',
            'model_type' => 'required|string|in:App\Models\Purchase,App\Models\File',
            'model_id' => 'required|integer',
            'folder_name' => 'nullable|string|max:255',
            'personal' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $modelClass = $request->input('model_type');
            $model = $modelClass::findOrFail($request->input('model_id'));

            $isFavorite = FavoritesFacade::isFavorite(
                $request->input('owner_id'),
                $model,
                $request->input('folder_name'),
                $request->boolean('personal', false)
            );

            return ApiResponse::success('Favorite status checked', [
                'is_favorite' => $isFavorite
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to check favorite status', $e->getMessage(), 500);
        }
    }

    /**
     * Get user's favorites
     */
    public function getFavorites(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|integer|exists:moonshine_users,id',
            'folder_name' => 'nullable|string|max:255',
            'personal' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $favorites = FavoritesFacade::getFavorites(
                $request->input('owner_id'),
                $request->input('folder_name'),
                $request->boolean('personal', false)
            );

            return ApiResponse::success('Favorites retrieved', [
                'favorites' => $favorites,
                'count' => $favorites->count()
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get favorites', $e->getMessage(), 500);
        }
    }

    /**
     * Create or find a folder
     */
    public function createFolder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|integer|exists:moonshine_users,id',
            'name' => 'required|string|max:255',
            'personal' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $folder = FavoritesFacade::createOrFindFolder(
                $request->input('owner_id'),
                $request->input('name'),
                $request->boolean('personal', false)
            );

            return ApiResponse::success('Folder created or found', [
                'folder' => $folder
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create folder', $e->getMessage(), 500);
        }
    }

    /**
     * Get all folders for user
     */
    public function getFolders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'nullable|integer|exists:moonshine_users,id',
            'order_by' => 'nullable|string|in:name,created_at,updated_at'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $folders = FavoritesFacade::getAllFoldersForOwner(
                $request->input('owner_id'),
                $request->input('order_by', 'name')
            );

            return ApiResponse::success('Folders retrieved', [
                'folders' => $folders,
                'count' => $folders->count()
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get folders', $e->getMessage(), 500);
        }
    }

    /**
     * Delete a folder
     */
    public function deleteFolder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|integer|exists:moonshine_users,id',
            'folder_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $result = FavoritesFacade::deleteFolder(
                $request->input('owner_id'),
                $request->input('folder_id')
            );

            if ($result) {
                return ApiResponse::success('Folder deleted successfully');
            } else {
                return ApiResponse::error('Failed to delete folder', null, 500);
            }

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete folder', $e->getMessage(), 500);
        }
    }

    /**
     * Get favorite count
     */
    public function getFavoriteCount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|integer|exists:moonshine_users,id',
            'folder_name' => 'nullable|string|max:255',
            'personal' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        try {
            $count = FavoritesFacade::getFavoriteCount(
                $request->input('owner_id'),
                $request->input('folder_name'),
                $request->boolean('personal', false)
            );

            return ApiResponse::success('Favorite count retrieved', [
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get favorite count', $e->getMessage(), 500);
        }
    }
}
