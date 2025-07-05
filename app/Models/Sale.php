<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_name',
        'sale_date',
        'bill_number',
        'status',
        'total_amount',
        'total_gst_amount',
        'notes',
        'customer_id',
    ];

    /**
     * Get the sale items for the sale.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

        /**
     * Get the customer that owns the sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getSubtotalAmountAttribute(): float
{
    return round($this->total_amount - $this->total_gst_amount, 2);
}

}