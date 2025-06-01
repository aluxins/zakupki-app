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
        Schema::create('purchases', function (Blueprint $table) {
            /*
            published_at 	"2025-05-28T12:37:27.395000"
	        max_price 		646000.00
	        object_info 	"Поставка сенсорно-динамического комплекса для нужд МБДОУ ЦРР-Д/С № 6 ЗАТО г. Радужный Владимирской области"
	        purchase_number "0328300000325000070"
	        purchase_type 	"epNotificationEF2020"
	        region 			33
            classifier      32.30
            */
            $table->id();
            $table->string('purchase_number')->unique();
            $table->string('purchase_type')->index();
            $table->string('classifier')->index();
            $table->text('object_info')->fulltext();
            $table->decimal('max_price', total: 12, places: 2);
            $table->integer('region')->index();
            $table->dateTime('published_at', precision: 6);
            $table->timestamps();
        });
        DB::statement('ALTER TABLE purchases ENGINE = MyISAM');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
