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
        if (! Schema::hasColumn('material_receipts', 'remarks')) {
            Schema::table('material_receipts', function (Blueprint $table) {
                $table->string('remarks')->nullable()->after('received_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('material_receipts', 'remarks')) {
            Schema::table('material_receipts', function (Blueprint $table) {
                $table->dropColumn('remarks');
            });
        }
    }
};
