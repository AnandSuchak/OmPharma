<?php

namespace App\Models;

use App\Models\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory,SoftDeletes, BelongsToShop;

    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'gst',
        'address',
        'dln',
    ];

    public function purchaseBills(): HasMany
    {
        return $this->hasMany(PurchaseBill::class);
    }
}