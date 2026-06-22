<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->enum('stock_type', ['raw_material', 'finished_goods']);
            $table->foreignId('raw_material_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sku_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('direction', ['increase', 'decrease']);
            $table->decimal('qty', 15, 4);
            $table->string('reason');
            $table->string('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['stock_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
