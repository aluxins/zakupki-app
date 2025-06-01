<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->text('file_name');
            $table->integer('file_size');
            $table->text('description');
            $table->text('url');
            $table->string('hash')->unique();
            $table->string('purchase_number');
            $table->timestamps();
        });
        DB::statement('ALTER TABLE files ENGINE = MyISAM');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
