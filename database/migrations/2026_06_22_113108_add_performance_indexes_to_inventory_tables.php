<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->index(['raw_material_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });

        Schema::table('finished_goods_transactions', function (Blueprint $table) {
            $table->index(['sku_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });

        Schema::table('material_receipts', function (Blueprint $table) {
            $table->index('received_date');
            $table->index('batch_no');
        });

        Schema::table('repackaging_batches', function (Blueprint $table) {
            $table->index('repackaged_date');
            $table->index('batch_no');
        });

        Schema::table('dispatches', function (Blueprint $table) {
            $table->index('dispatched_date');
        });

        Schema::table('finished_goods_batches', function (Blueprint $table) {
            $table->index('batch_no');
            $table->index('produced_date');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex(['raw_material_id', 'type']);
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('finished_goods_transactions', function (Blueprint $table) {
            $table->dropIndex(['sku_id', 'type']);
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('material_receipts', function (Blueprint $table) {
            $table->dropIndex(['received_date']);
            $table->dropIndex(['batch_no']);
        });

        Schema::table('repackaging_batches', function (Blueprint $table) {
            $table->dropIndex(['repackaged_date']);
            $table->dropIndex(['batch_no']);
        });

        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropIndex(['dispatched_date']);
        });

        Schema::table('finished_goods_batches', function (Blueprint $table) {
            $table->dropIndex(['batch_no']);
            $table->dropIndex(['produced_date']);
        });
    }
};
