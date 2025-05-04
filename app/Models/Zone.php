<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Zone extends Model
{
    use HasFactory,SoftDeletes;
    public $timestamps = false;
    protected $casts = [
        'horaires' => 'array',
    ];

    public function pricings()
    {
        return $this->hasMany(Pricing::class);
    }

    public function ville()
    {
        return $this->belongsTo(Ville::class);
    }
}
