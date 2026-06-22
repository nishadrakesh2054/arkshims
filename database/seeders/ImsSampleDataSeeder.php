<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\FinishedGoodsBatch;
use App\Models\MaterialReceipt;
use App\Models\ProductCategory;
use App\Models\RawMaterial;
use App\Models\RepackagingBatch;
use App\Models\RepackagingFormula;
use App\Models\Sku;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ImsSampleDataSeeder extends Seeder
{
    /**
     * Seed sample IMS data for demos and tests.
     */
    public function run(): void
    {
        $kg = Unit::query()->where('symbol', 'kg')->firstOrFail();
        $gm = Unit::query()->where('symbol', 'gm')->firstOrFail();

        $coffeeCategory = ProductCategory::query()->firstOrCreate(
            ['code' => 'COFFEE'],
            ['name' => 'Coffee'],
        );

        $creamerCategory = ProductCategory::query()->firstOrCreate(
            ['code' => 'CREAMER'],
            ['name' => 'Creamer'],
        );

        $coffeeGold = Brand::query()->firstOrCreate(
            ['name' => 'Gold', 'product_category_id' => $coffeeCategory->id],
        );

        $coffeeOriginal = Brand::query()->firstOrCreate(
            ['name' => 'Original', 'product_category_id' => $coffeeCategory->id],
        );

        $creamerOriginal = Brand::query()->firstOrCreate(
            ['name' => 'Original', 'product_category_id' => $creamerCategory->id],
        );

        $supplierChina = Supplier::query()->firstOrCreate(
            ['name' => 'China Coffee Imports'],
            ['phone' => '+86-555-0100', 'email' => 'liwei@china-coffee.test', 'country' => 'China'],
        );

        $supplierItaly = Supplier::query()->firstOrCreate(
            ['name' => 'Italy Dairy Co'],
            ['phone' => '+39-555-0200', 'email' => 'marco@italy-dairy.test', 'country' => 'Italy'],
        );

        $coffeeBulk = RawMaterial::query()->firstOrCreate(
            ['name' => 'Coffee Bulk'],
            ['unit_id' => $kg->id, 'minimum_stock' => 50],
        );

        $creamerBulk = RawMaterial::query()->firstOrCreate(
            ['name' => 'Creamer Bulk'],
            ['unit_id' => $kg->id, 'minimum_stock' => 30],
        );

        if ($coffeeBulk->materialReceipts()->doesntExist()) {
            MaterialReceipt::query()->create([
                'raw_material_id' => $coffeeBulk->id,
                'supplier_id' => $supplierChina->id,
                'batch_no' => 'COF-BATCH-001',
                'qty' => 500,
                'unit_id' => $kg->id,
                'received_date' => now()->subDays(10)->toDateString(),
                'remarks' => 'Arabica beans from China',
            ]);

            MaterialReceipt::query()->create([
                'raw_material_id' => $coffeeBulk->id,
                'supplier_id' => $supplierItaly->id,
                'batch_no' => 'COF-BATCH-002',
                'qty' => 300,
                'unit_id' => $kg->id,
                'received_date' => now()->subDays(5)->toDateString(),
                'remarks' => 'Robusta blend from Italy',
            ]);
        }

        if ($creamerBulk->materialReceipts()->doesntExist()) {
            MaterialReceipt::query()->create([
                'raw_material_id' => $creamerBulk->id,
                'supplier_id' => $supplierItaly->id,
                'batch_no' => 'CRM-BATCH-001',
                'qty' => 250,
                'unit_id' => $kg->id,
                'received_date' => now()->subDays(7)->toDateString(),
                'remarks' => 'Powdered creamer bulk',
            ]);
        }

        $skuDefinitions = [
            [
                'sku_code' => 'SKU-CG-50',
                'name' => 'Coffee Gold 50gm Pouch',
                'product_category_id' => $coffeeCategory->id,
                'brand_id' => $coffeeGold->id,
                'weight' => 50,
                'unit_id' => $gm->id,
                'packaging_type' => 'Pouch',
                'formula' => ['raw_material_id' => $coffeeBulk->id, 'qty' => 0.05, 'unit_id' => $kg->id],
            ],
            [
                'sku_code' => 'SKU-CG-100',
                'name' => 'Coffee Gold 100gm Pouch',
                'product_category_id' => $coffeeCategory->id,
                'brand_id' => $coffeeGold->id,
                'weight' => 100,
                'unit_id' => $gm->id,
                'packaging_type' => 'Pouch',
                'formula' => ['raw_material_id' => $coffeeBulk->id, 'qty' => 0.10, 'unit_id' => $kg->id],
            ],
            [
                'sku_code' => 'SKU-CO-1KG',
                'name' => 'Coffee Original 1kg Pack',
                'product_category_id' => $coffeeCategory->id,
                'brand_id' => $coffeeOriginal->id,
                'weight' => 1,
                'unit_id' => $kg->id,
                'packaging_type' => 'Pack',
                'formula' => ['raw_material_id' => $coffeeBulk->id, 'qty' => 1, 'unit_id' => $kg->id],
            ],
            [
                'sku_code' => 'SKU-CR-10',
                'name' => 'Creamer Original 10gm Sachet',
                'product_category_id' => $creamerCategory->id,
                'brand_id' => $creamerOriginal->id,
                'weight' => 10,
                'unit_id' => $gm->id,
                'packaging_type' => 'Sachet',
                'formula' => ['raw_material_id' => $creamerBulk->id, 'qty' => 0.01, 'unit_id' => $kg->id],
            ],
            [
                'sku_code' => 'SKU-CR-200',
                'name' => 'Creamer Original 200gm Jar',
                'product_category_id' => $creamerCategory->id,
                'brand_id' => $creamerOriginal->id,
                'weight' => 200,
                'unit_id' => $gm->id,
                'packaging_type' => 'Jar',
                'formula' => ['raw_material_id' => $creamerBulk->id, 'qty' => 0.20, 'unit_id' => $kg->id],
            ],
            [
                'sku_code' => 'SKU-CG-BOX12',
                'name' => 'Coffee Gold 12x50gm Box',
                'product_category_id' => $coffeeCategory->id,
                'brand_id' => $coffeeGold->id,
                'weight' => 600,
                'unit_id' => $gm->id,
                'packaging_type' => 'Box',
                'formula' => ['raw_material_id' => $coffeeBulk->id, 'qty' => 0.60, 'unit_id' => $kg->id],
            ],
        ];

        foreach ($skuDefinitions as $definition) {
            $formula = $definition['formula'];
            unset($definition['formula']);

            $sku = Sku::query()->updateOrCreate(
                ['sku_code' => $definition['sku_code']],
                array_merge($definition, ['is_active' => true]),
            );

            RepackagingFormula::query()->updateOrCreate(
                [
                    'sku_id' => $sku->id,
                    'raw_material_id' => $formula['raw_material_id'],
                ],
                [
                    'qty' => $formula['qty'],
                    'unit_id' => $formula['unit_id'],
                ],
            );
        }

        if (RepackagingBatch::query()->doesntExist()) {
            $pouch50 = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();
            $pouch100 = Sku::query()->where('sku_code', 'SKU-CG-100')->firstOrFail();
            $sachet10 = Sku::query()->where('sku_code', 'SKU-CR-10')->firstOrFail();
            $jar200 = Sku::query()->where('sku_code', 'SKU-CR-200')->firstOrFail();
            $box12 = Sku::query()->where('sku_code', 'SKU-CG-BOX12')->firstOrFail();

            RepackagingBatch::query()->create([
                'sku_id' => $pouch50->id,
                'batch_no' => 'REPACK-POUCH50-001',
                'quantity' => 200,
                'repackaged_date' => now()->subDays(2)->toDateString(),
                'remarks' => '50gm pouch run',
            ]);

            RepackagingBatch::query()->create([
                'sku_id' => $pouch100->id,
                'batch_no' => 'REPACK-POUCH100-001',
                'quantity' => 100,
                'repackaged_date' => now()->subDay()->toDateString(),
                'remarks' => '100gm pouch run',
            ]);

            RepackagingBatch::query()->create([
                'sku_id' => $sachet10->id,
                'batch_no' => 'REPACK-SACHET-001',
                'quantity' => 500,
                'repackaged_date' => now()->subDay()->toDateString(),
                'remarks' => '10gm sachet run',
            ]);

            RepackagingBatch::query()->create([
                'sku_id' => $jar200->id,
                'batch_no' => 'REPACK-JAR-001',
                'quantity' => 50,
                'repackaged_date' => now()->toDateString(),
                'remarks' => '200gm jar run',
            ]);

            RepackagingBatch::query()->create([
                'sku_id' => $box12->id,
                'batch_no' => 'REPACK-BOX-001',
                'quantity' => 20,
                'repackaged_date' => now()->toDateString(),
                'remarks' => '12x50gm box run',
            ]);
        }

        if (Dispatch::query()->doesntExist()) {
            $pouch50 = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();
            $fgBatch = FinishedGoodsBatch::query()
                ->where('sku_id', $pouch50->id)
                ->first();

            $dispatch = Dispatch::query()->create([
                'dispatch_no' => 'DISP-001',
                'customer_name' => 'Metro Retail Store',
                'dispatched_date' => now()->toDateString(),
                'remarks' => 'Sample dispatch',
            ]);

            DispatchItem::query()->create([
                'dispatch_id' => $dispatch->id,
                'sku_id' => $pouch50->id,
                'finished_goods_batch_id' => $fgBatch?->id,
                'quantity' => 50,
            ]);
        }
    }
}
