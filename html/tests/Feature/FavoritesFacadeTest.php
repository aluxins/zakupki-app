<?php

namespace Tests\Feature;

use App\Http\Services\Favorites\FavoritesFacade;
use App\Http\Services\Favorites\FavoritesInterface;
use App\Models\Favorite;
use App\Models\FavoriteFolder;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoritesFacadeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Purchase $purchase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->purchase = Purchase::factory()->create();
    }

    public function test_favorites_facade_can_be_resolved_from_container()
    {
        $service = app(FavoritesInterface::class);
        
        $this->assertInstanceOf(FavoritesInterface::class, $service);
    }

    public function test_add_to_favorites_creates_favorite_record()
    {
        $favorite = FavoritesFacade::addToFavorites(
            $this->user->id,
            $this->purchase
        );

        $this->assertInstanceOf(Favorite::class, $favorite);
        $this->assertEquals($this->user->id, $favorite->moonshine_users_id);
        $this->assertEquals(Purchase::class, $favorite->model_name);
        $this->assertEquals($this->purchase->id, $favorite->model_id);
    }

    public function test_add_to_favorites_with_folder()
    {
        $favorite = FavoritesFacade::addToFavorites(
            $this->user->id,
            $this->purchase,
            'Test Folder',
            true
        );

        $this->assertInstanceOf(Favorite::class, $favorite);
        $this->assertNotNull($favorite->favorite_folder_id);

        // Check if folder was created
        $folder = FavoriteFolder::find($favorite->favorite_folder_id);
        $this->assertNotNull($folder);
        $this->assertEquals('Test Folder', $folder->name);
        $this->assertEquals($this->user->id, $folder->moonshine_users_id);
    }

    public function test_is_favorite_returns_correct_status()
    {
        // Initially not favorite
        $this->assertFalse(
            FavoritesFacade::isFavorite($this->user->id, $this->purchase)
        );

        // Add to favorites
        FavoritesFacade::addToFavorites($this->user->id, $this->purchase);

        // Now should be favorite
        $this->assertTrue(
            FavoritesFacade::isFavorite($this->user->id, $this->purchase)
        );
    }

    public function test_toggle_favorite_adds_when_not_favorite()
    {
        $result = FavoritesFacade::toggleFavorite($this->user->id, $this->purchase);

        $this->assertTrue($result);
        $this->assertTrue(
            FavoritesFacade::isFavorite($this->user->id, $this->purchase)
        );
    }

    public function test_toggle_favorite_removes_when_already_favorite()
    {
        // Add to favorites first
        FavoritesFacade::addToFavorites($this->user->id, $this->purchase);

        // Toggle should remove
        $result = FavoritesFacade::toggleFavorite($this->user->id, $this->purchase);

        $this->assertFalse($result);
        $this->assertFalse(
            FavoritesFacade::isFavorite($this->user->id, $this->purchase)
        );
    }

    public function test_get_favorites_returns_collection()
    {
        FavoritesFacade::addToFavorites($this->user->id, $this->purchase);

        $favorites = FavoritesFacade::getFavorites($this->user->id);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $favorites);
        $this->assertCount(1, $favorites);
    }

    public function test_get_favorite_count_returns_correct_number()
    {
        $this->assertEquals(0, FavoritesFacade::getFavoriteCount($this->user->id));

        FavoritesFacade::addToFavorites($this->user->id, $this->purchase);

        $this->assertEquals(1, FavoritesFacade::getFavoriteCount($this->user->id));
    }

    public function test_remove_from_favorites()
    {
        // Add to favorites first
        FavoritesFacade::addToFavorites($this->user->id, $this->purchase);

        // Remove from favorites
        $result = FavoritesFacade::removeFromFavorites($this->user->id, $this->purchase);

        $this->assertTrue($result);
        $this->assertFalse(
            FavoritesFacade::isFavorite($this->user->id, $this->purchase)
        );
    }

    public function test_create_folder()
    {
        $folder = FavoritesFacade::createOrFindFolder(
            $this->user->id,
            'Test Folder',
            true
        );

        $this->assertInstanceOf(FavoriteFolder::class, $folder);
        $this->assertEquals('Test Folder', $folder->name);
        $this->assertEquals($this->user->id, $folder->moonshine_users_id);
    }

    public function test_get_all_folders_for_owner()
    {
        // Create some folders
        FavoritesFacade::createOrFindFolder($this->user->id, 'Folder 1', true);
        FavoritesFacade::createOrFindFolder($this->user->id, 'Folder 2', true);

        $folders = FavoritesFacade::getAllFoldersForOwner($this->user->id);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $folders);
        $this->assertCount(2, $folders);
    }

    public function test_clear_all_favorites()
    {
        // Add some favorites
        FavoritesFacade::addToFavorites($this->user->id, $this->purchase);
        
        $anotherPurchase = Purchase::factory()->create();
        FavoritesFacade::addToFavorites($this->user->id, $anotherPurchase);

        $this->assertEquals(2, FavoritesFacade::getFavoriteCount($this->user->id));

        // Clear all
        $result = FavoritesFacade::clearAllFavorites($this->user->id);

        $this->assertTrue($result);
        $this->assertEquals(0, FavoritesFacade::getFavoriteCount($this->user->id));
    }

    public function test_get_favorites_by_model()
    {
        $anotherPurchase = Purchase::factory()->create();
        
        // Add purchases to favorites
        FavoritesFacade::addToFavorites($this->user->id, $this->purchase);
        FavoritesFacade::addToFavorites($this->user->id, $anotherPurchase);

        $purchaseFavorites = FavoritesFacade::getFavoritesByModel(
            $this->user->id,
            Purchase::class
        );

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $purchaseFavorites);
        $this->assertCount(2, $purchaseFavorites);
    }
}
