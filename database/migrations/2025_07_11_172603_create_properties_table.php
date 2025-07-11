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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner'); // ممكن يكون تابع للمستخدم و ممكن يكون للمكتب
            $table->string('ad_number')->unique();
            $table->string('governorate')->nullable(); // المحافظ
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->string('location');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('area', 10, 2)->nullable();
            $table->integer('floor_number')->nullable();
            $table->enum('ad_type', ['sale', 'rent'])->default('sale');
            $table->enum('type', ['apartment', 'villa', 'office', 'land', 'commercial', 'farm', 'building', 'chalet'])->default('apartment');
            $table->enum('position', ['available', 'sold', 'rented'])->default('available');
            $table->boolean('is_offer')->default(false);
            $table->timestamp('offer_expires_at')->nullable();
            $table->string('currency')->default('USD');
            $table->integer('views')->default(0);
            $table->integer('bathrooms');
            $table->integer('rooms');
            $table->enum('seller_type', ['owner', 'agent', 'developer'])->default('owner');
            $table->string('direction');
            $table->enum('furnishing', ['furnished', 'unfurnished', 'semi-furnished']);
            $table->text('features')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
