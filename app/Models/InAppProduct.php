<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InAppProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_id',
        'name',
        'sku',
        'min_price',
        'max_price',
        'set_price',
        'isActive',
        'isAndroidUpload',
        'isAppleUpload',
    ];

    protected $casts = [
        'isActive' => 'boolean',
        'isAndroidUpload' => 'boolean',
        'isAppleUpload' => 'boolean',
        'min_price' => 'integer',
        'max_price' => 'integer',
        'set_price' => 'integer',
        'currency_id' => 'integer',
    ];
    public function currency()
    {
        return $this->belongsTo(Currency::class,'currency_id');
    }
}
