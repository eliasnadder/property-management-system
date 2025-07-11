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
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('phone')->unique();
            $table->string('password');
            $table->enum('type', ['office'])->default('office');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('document_path')->nullable(); // ملف الوثيقة
            $table->integer('free_ads')->default(0); // عدد الإعلانات المجانية
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
