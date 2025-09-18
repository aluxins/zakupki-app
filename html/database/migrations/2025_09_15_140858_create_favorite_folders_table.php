<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('favorite_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moonshine_users_id')->nullable()->index()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('name');
            $table->timestamps();

            $table->unique(['moonshine_users_id', 'name']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('favorite_folders');
    }
};
