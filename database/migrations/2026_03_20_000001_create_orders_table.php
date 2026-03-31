<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->nullable();
            $table->foreignId('distributor_id')->nullable()->constrained('distributors')->cascadeOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('type')->nullable()->comment('Transportation type e.g. Full Container, Part Loader');
            $table->string('remarks', 255)->nullable();
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->decimal('total_cbm', 10, 3)->nullable();
            $table->string('status')->default('pending')->comment('pending, confirmed, failed, declined, completed');
            $table->string('shipping_status')->default('pending')->comment('pending, approved, confirmed, in-process, delivered, cancelled, declined');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
