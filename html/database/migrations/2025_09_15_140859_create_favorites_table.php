<?php

use App\Models\FavoriteFolder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moonshine_users_id')->index()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('model_name');
            $table->bigInteger('model_id');
            $table->foreignIdFor(FavoriteFolder::class)->nullable()->index()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['moonshine_users_id', 'model_name', 'model_id', 'favorite_folder_id'], 'favorites_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('favorites');
    }
};
