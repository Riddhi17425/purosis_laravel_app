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
        Schema::create('distributors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_no', 20);
            $table->string('whatsapp_no', 20)->nullable();
            $table->integer('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('area')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('shipping_address_line')->nullable();
            $table->string('shipping_address_pincode')->nullable();
            $table->tinyInteger('is_active')->default(0)->comment('0 = Inactive, 1 = Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributors');
    }
};
