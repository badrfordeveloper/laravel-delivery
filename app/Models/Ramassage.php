<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ramassage extends Model
{
    use HasFactory;


    public function colis()
    {
        return $this->hasMany(Colis::class);
    }
}
