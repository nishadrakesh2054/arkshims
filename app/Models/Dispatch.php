<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\InventoryGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dispatch extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'dispatch_no',
        'customer_name',
        'dispatched_date',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dispatched_date' => 'date',
        ];
    }

    public function items()
    {
        return $this->hasMany(DispatchItem::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Dispatch $dispatch): void {
            FinishedGoodsTransaction::query()
                ->where('reference_type', 'dispatch')
                ->where('reference_id', $dispatch->id)
                ->delete();
        });
    }

    public function delete(): ?bool
    {
        if (DB::transactionLevel() > 0) {
            return parent::delete();
        }

        return InventoryGuard::transaction(function (): ?bool {
            return parent::delete();
        });
    }

    /**
     * @param  array<int, array{sku_id?: int, quantity?: int, finished_goods_batch_id?: int|null}>  $items
     */
    public static function validateStockForItems(array $items): void
    {
        InventoryGuard::assertDispatchItemsStock($items);
    }
}
