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
        Schema::table('repackaging_batches', function (Blueprint $table) {
            $table->foreignId('sku_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('batch_no')->after('sku_id');
            $table->unsignedInteger('quantity')->after('batch_no');
            $table->date('repackaged_date')->nullable()->after('quantity');
            $table->string('remarks')->nullable()->after('repackaged_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repackaging_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sku_id');
            $table->dropColumn(['batch_no', 'quantity', 'repackaged_date', 'remarks']);
        });
    }
};
