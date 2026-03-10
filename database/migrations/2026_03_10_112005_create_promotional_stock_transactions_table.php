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
        Schema::create('promotional_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('serial_no')->nullable();
            $table->enum('type', ['inward', 'outward'])->nullable()->comment('inward or outward');
            $table->string('item_id')->nullable();
            $table->integer('qty')->nullable()->change();
            $table->string('recipient_type')->nullable();
            $table->string('notes', 700)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotional_stock_transactions');
    }
};
