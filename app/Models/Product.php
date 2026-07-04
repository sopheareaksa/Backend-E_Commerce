<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'product_name',
        'product_category',
        'product_price',
        'product_discount',
        'product_special_offer',
        'product_image',
        'product_image2',
        'product_image3',
        'product_image4',
        'created_at',
    ];

    protected $casts = [
        'product_price' => 'decimal:2',
        'product_discount' => 'decimal:2',
        'created_at' => 'datetime',
    ];
}
