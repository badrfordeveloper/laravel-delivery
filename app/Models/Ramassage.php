<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ramassage extends Model
{
    use HasFactory,SoftDeletes;


    public function colis()
    {
        return $this->hasMany(Colis::class);
    }

    public function histories()
    {
        return $this->morphMany(History::class, 'historiable')->orderBy('id', 'desc');;
    }
}
