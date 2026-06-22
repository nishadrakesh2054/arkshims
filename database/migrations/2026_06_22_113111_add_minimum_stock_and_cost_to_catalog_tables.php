<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            if (! Schema::hasColumn('raw_materials', 'cost_per_unit')) {
                $table->decimal('cost_per_unit', 15, 4)->default(0)->after('minimum_stock');
            }
        });

        Schema::table('skus', function (Blueprint $table) {
            if (! Schema::hasColumn('skus', 'minimum_stock')) {
                $table->unsignedInteger('minimum_stock')->default(0)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropColumn('cost_per_unit');
        });

        Schema::table('skus', function (Blueprint $table) {
            $table->dropColumn('minimum_stock');
        });
    }
};
