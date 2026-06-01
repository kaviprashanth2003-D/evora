<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 50)->unique();
            $table->string('name', 150);
            $table->string('category', 50);
            $table->text('description');
            $table->string('image1', 255);
            $table->string('image2', 255)->nullable();
            $table->string('image3', 255)->nullable();
            $table->string('image4', 255)->nullable();
            $table->decimal('original_price', 10, 2);
            $table->decimal('discount_price', 10, 2);
            $table->boolean('discount_active')->default(false);
            $table->string('offer_badge', 50)->nullable();
            $table->integer('stock_xs')->default(0);
            $table->integer('stock_s')->default(0);
            $table->integer('stock_m')->default(0);
            $table->integer('stock_l')->default(0);
            $table->integer('stock_xl')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('products');
    }
};
