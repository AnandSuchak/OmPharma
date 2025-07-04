<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

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