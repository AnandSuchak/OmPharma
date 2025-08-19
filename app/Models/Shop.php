<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'address',
        'contact_person',
        'contact_phone',
    ];

    /**
     * Define the relationship to the User model.
     * A shop can have many users.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
