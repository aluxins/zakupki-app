<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dateTime('finished_at')->after('published_at')->nullable()->index();
            $table->dateTime('delivered_at')->after('finished_at')->nullable()->index();
            $table->enum('placing_way', ['', 'аукцион', 'котировок'])->after('type_fz')->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['finished_at', 'delivered_at', 'placing_way']);
        });
    }
};
