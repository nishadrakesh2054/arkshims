<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            if (! Schema::hasColumn('skus', 'packs_per_carton')) {
                $table->unsignedInteger('packs_per_carton')->nullable()->after('minimum_stock');
            }
        });

        Schema::table('finished_goods_batches', function (Blueprint $table) {
            $table->dropForeign(['repackaging_batch_id']);
        });

        Schema::table('finished_goods_batches', function (Blueprint $table) {
            $table->dropUnique(['repackaging_batch_id']);
        });

        Schema::table('finished_goods_batches', function (Blueprint $table) {
            $table->foreignId('repackaging_batch_id')->nullable()->change();
            $table->foreign('repackaging_batch_id')
                ->references('id')
                ->on('repackaging_batches')
                ->nullOnDelete();
        });

        Schema::table('finished_goods_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('finished_goods_batches', 'finished_goods_receipt_id')) {
                $table->foreignId('finished_goods_receipt_id')
                    ->nullable()
                    ->after('repackaging_batch_id')
                    ->constrained()
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('finished_goods_batches', function (Blueprint $table) {
            if (Schema::hasColumn('finished_goods_batches', 'finished_goods_receipt_id')) {
                $table->dropConstrainedForeignId('finished_goods_receipt_id');
            }
        });

        Schema::table('skus', function (Blueprint $table) {
            if (Schema::hasColumn('skus', 'packs_per_carton')) {
                $table->dropColumn('packs_per_carton');
            }
        });
    }
};
