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
        Schema::create('material_receipts', function (Blueprint $table) {

            $table->id();
        
            $table->foreignId('raw_material_id')->constrained()->cascadeOnDelete();
        
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
        
            $table->string('batch_no');
        
            $table->decimal('qty',15,4);
        
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
        
            $table->decimal('base_qty',15,4);
        
            $table->date('received_date')->nullable();
            $table->string('remarks')->nullable();
        
            $table->timestamps();   
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_receipts');
    }
};
