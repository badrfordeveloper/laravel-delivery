<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Colis extends Model
{
    use HasFactory,SoftDeletes;

    public function ramassage()
    {
        return $this->belongsTo(Ramassage::class);
    }

    public function histories()
    {
        return $this->morphMany(History::class, 'historiable');
    }
}
