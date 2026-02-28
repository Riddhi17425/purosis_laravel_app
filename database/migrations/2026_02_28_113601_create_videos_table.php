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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->string('media_file')->nullable();
            $table->string('thumbnail_image')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->string('description', 1000)->nullable();
            $table->tinyInteger('is_featured')->default(0)->comment('0 = Not Featured, 1 = Featured');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
