<?php

namespace App\Models;

use App\Models\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryLog extends Model
{
    use HasFactory, BelongsToShop;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_logs'; // Ensure this matches your table name

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inventory_id',
        'medicine_id',
        'batch_number',
        'transaction_type',
        'transaction_reference_type',
        'transaction_reference_id',
        'quantity_change',
        'new_quantity_on_hand',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity_change' => 'decimal:2',
        'new_quantity_on_hand' => 'decimal:2',
        'created_at' => 'datetime', // Cast timestamps for Carbon instances
        'updated_at' => 'datetime',
    ];

    /**
     * Get the inventory record that owns the log.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Get the medicine associated with the log.
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    /**
     * Get the owning transaction reference model (e.g., SaleItem, PurchaseBillItem).
     */
    public function transaction_reference(): MorphTo
    {
        return $this->morphTo();
    }
}