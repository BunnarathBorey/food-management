<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;
    protected $table = 'foods';
    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'food_image',
        'availability',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'availability' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

}
