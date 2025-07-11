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
        Schema::create('wanted_properties', function (Blueprint $table) {
            $table->id();
            $table->morphs('wanted_Pable');
            $table->enum('buy_or_rent', ['buy', 'rent']);
            $table->string('governorate')->nullable(); // المحافظة
            $table->string('area')->nullable(); // المنطقة
            $table->decimal('budget', 10, 2)->nullable(); // الميزانية
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wanted_properties');
    }
};
