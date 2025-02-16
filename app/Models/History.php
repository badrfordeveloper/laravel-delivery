<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        // Listen for the 'creating' event
        static::creating(function ($history) {
            $user = auth()->user();
            $history->creator_name = $user->lastName;
            $history->created_by = $user->id;
        });


    }

    public function historiable()
    {
        return $this->morphTo();
    }
}
