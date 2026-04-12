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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->nullable()->constrained('distributors')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->integer('qty')->nullable();
            // $table->string('color_code')->nullable();
            $table->foreignId('color_id')->nullable()->constrained('product_colors')->cascadeOnDelete();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('units_per_box')->nullable();
            $table->decimal('weight_per_box', 10, 2)->nullable();
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->decimal('total_cbm', 10, 3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
