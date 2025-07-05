<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseBillItem extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'purchase_bill_id',
        'medicine_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'purchase_price',
        'ptr',
        'gst_rate',
        'discount_percentage',
            'sale_price',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}