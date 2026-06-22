<?php

namespace App\Console\Commands;

use App\Models\FinishedGoodsBatch;
use App\Models\FinishedGoodsTransaction;
use App\Models\RepackagingBatch;
use Illuminate\Console\Command;

class BackfillFinishedGoods extends Command
{
    protected $signature = 'ims:backfill-finished-goods';

    protected $description = 'Create finished goods batches and IN transactions for existing repackaging batches';

    public function handle(): int
    {
        $batches = RepackagingBatch::query()
            ->whereDoesntHave('finishedGoodsBatch')
            ->get();

        if ($batches->isEmpty()) {
            $this->components->info('No repackaging batches need backfilling.');

            return self::SUCCESS;
        }

        foreach ($batches as $batch) {
            $finishedGoodsBatch = FinishedGoodsBatch::query()->create([
                'repackaging_batch_id' => $batch->id,
                'sku_id' => $batch->sku_id,
                'batch_no' => $batch->batch_no,
                'quantity' => $batch->quantity,
                'produced_date' => $batch->repackaged_date,
            ]);

            FinishedGoodsTransaction::query()->create([
                'sku_id' => $batch->sku_id,
                'type' => 'IN',
                'qty' => $batch->quantity,
                'reference_type' => 'repackaging_batch',
                'reference_id' => $batch->id,
                'finished_goods_batch_id' => $finishedGoodsBatch->id,
            ]);

            $this->line("Backfilled FG batch for repackaging batch #{$batch->id} ({$batch->batch_no})");
        }

        $this->components->success("Backfilled {$batches->count()} finished goods batch(es).");

        return self::SUCCESS;
    }
}
