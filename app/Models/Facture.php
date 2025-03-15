<?php

namespace App\Models;

use App\Models\History;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Facture extends Model
{
    use HasFactory,SoftDeletes;

    public function histories()
    {
        return $this->morphMany(History::class, 'historiable')->orderBy('id', 'desc');;
    }

    public function colis()
    {
        return $this->hasMany(Colis::class);
    }

    public function ramassages()
    {
        return $this->hasMany(Ramassage::class);
    }

    public function retours()
    {
        return $this->hasMany(Retour::class);
    }
}
