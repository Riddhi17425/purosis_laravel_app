<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->integer('qty')->nullable();
            $table->string('color_code')->nullable();
            $table->foreignId('color_id')->nullable()->constrained('product_colors')->nullOnDelete();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('units_per_box')->nullable();
            $table->decimal('weight_per_box', 10, 2)->nullable();
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->decimal('total_cbm', 10, 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_products');
    }
};
