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
        Schema::table('repackaging_formulas', function (Blueprint $table) {
            $table->foreignId('sku_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('raw_material_id')->after('sku_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 15, 4)->after('raw_material_id');
            $table->foreignId('unit_id')->after('qty')->constrained()->cascadeOnDelete();
            $table->decimal('base_qty', 15, 4)->after('unit_id');

            $table->unique(['sku_id', 'raw_material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repackaging_formulas', function (Blueprint $table) {
            $table->dropUnique(['sku_id', 'raw_material_id']);
            $table->dropConstrainedForeignId('unit_id');
            $table->dropConstrainedForeignId('raw_material_id');
            $table->dropConstrainedForeignId('sku_id');
            $table->dropColumn(['qty', 'base_qty']);
        });
    }
};
