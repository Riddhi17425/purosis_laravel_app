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
        Schema::create('promotional_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('serial_no')->nullable();
            $table->string('item_name')->nullable();
            $table->integer('qty')->nullable()->change();
            $table->string('notes', 700)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotional_stocks');
    }
};
