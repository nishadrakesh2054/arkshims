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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
        
            $table->string('name');
            $table->string('symbol');
        
            $table->enum('type', [
                'weight',
                'volume',
                'count'
            ]);
        
            $table->decimal('conversion_factor', 12, 4);
        
            $table->boolean('is_base')->default(false);
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
