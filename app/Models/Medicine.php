<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'hsn_code',
        'description',
        'unit',
        'gst_rate',
        'pack',
        'company_name',
    ];

    public function purchaseBillItems(): HasMany
    {
        return $this->hasMany(PurchaseBillItem::class);
    }
    public function inventories() {
    return $this->hasMany(Inventory::class);
}
public function saleItems() {
    return $this->hasMany(SaleItem::class);
}
}   