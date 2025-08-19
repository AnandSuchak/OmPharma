<?php

namespace App\Models\Traits;

use App\Models\Scopes\ShopScope;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;

trait BelongsToShop
{
    /**
     * The "booted" method of the model.
     * This is automatically called when the model is initialized.
     */
    protected static function booted(): void
    {
        // Apply our global scope to automatically filter queries.
        static::addGlobalScope(new ShopScope);

        // Add a creating event listener to automatically set the shop_id.
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->shop_id) {
                $model->shop_id = Auth::user()->shop_id;
            }
        });
    }

    /**
     * Define the relationship to the Shop model.
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
