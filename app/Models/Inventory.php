<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PurchaseBillItem;
use App\Models\Traits\BelongsToShop;

class Inventory extends Model
{
    use HasFactory,SoftDeletes, BelongsToShop;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'medicine_id',
        'quantity',
        'batch_number',
        'expiry_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'date',
    ];

    /**
     * Get the medicine that owns the inventory record.
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function purchaseBillItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseBillItem::class);
    }
}