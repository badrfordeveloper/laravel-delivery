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

    public function colisLivreur()
    {
        return $this->hasMany(Colis::class,'facture_livreur_id');
    }

    public function ramassagesLivreur()
    {
        return $this->hasMany(Ramassage::class,'facture_livreur_id');
    }

    public function retoursLivreur()
    {
        return $this->hasMany(Retour::class,'facture_livreur_id');
    }


    public function colisVendeur()
    {
        return $this->hasMany(Colis::class,'facture_vendeur_id');
    }

    public function ramassagesVendeur()
    {
        return $this->hasMany(Ramassage::class,'facture_vendeur_id');
    }

    public function retoursVendeur()
    {
        return $this->hasMany(Retour::class,'facture_vendeur_id');
    }
}
