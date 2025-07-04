<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'contact_number',
        'email',
        'address',
        'dln',
        'gst_number',
        'pan_number',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

}