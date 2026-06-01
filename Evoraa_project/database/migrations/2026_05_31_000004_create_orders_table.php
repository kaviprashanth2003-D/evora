<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_hash', 64)->unique();
            $table->string('customer_name', 100);
            $table->string('customer_email', 100);
            $table->string('customer_phone', 20);
            $table->text('customer_address');
            $table->string('city', 50);
            $table->string('zip', 20)->nullable();
            $table->string('delivery_tier', 50);
            $table->decimal('shipping_fee', 10, 2);
            $table->string('payment_method', 50);
            $table->string('receipt_path', 255)->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('status', 20)->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('orders');
    }
};
