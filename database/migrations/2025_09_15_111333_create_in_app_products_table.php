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
        Schema::create('in_app_products', function (Blueprint $table) {
            $table->id();
            $table->integer('currency_id')->unsigned();
            $table->string('name',100);
            $table->string('sku')->unique();
            $table->bigInteger('min_price');
            $table->bigInteger('max_price');
            $table->bigInteger('set_price')->nullable();
            $table->boolean('isActive')->default(true);
            $table->boolean('isAndroidUpload')->default(false);
            $table->boolean('isAppleUpload')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_app_products');
    }
};
