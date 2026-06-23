<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->foreignId('sku_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('cartons_count');
            $table->unsignedInteger('packs_per_carton');
            $table->string('carton_prefix')->nullable();
            $table->date('received_date');
            $table->string('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('received_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_goods_receipts');
    }
};
