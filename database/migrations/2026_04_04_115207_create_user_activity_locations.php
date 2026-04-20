<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_locations', function (Blueprint $table) {
            $table->id();

            // Who performed the action
            $table->enum('event_type', ['login', 'order']);
            $table->enum('actor_type', ['admin', 'dealer', 'distributor']);
            $table->unsignedBigInteger('actor_id');

            // Optional order reference
            $table->unsignedBigInteger('order_id')->nullable();

            // Request details
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Geo location details
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('address')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamp('event_at')->nullable();
            $table->string('device_name')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['actor_type', 'actor_id']);
            $table->index('event_type');
            $table->index('order_id');

            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_locations');
    }
};