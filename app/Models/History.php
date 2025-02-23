<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class History extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        // Listen for the 'creating' event
        static::creating(function ($history) {
            $user = auth()->user();
            $history->creator_name = $user->firstName ." ".$user->lastName ;
            $history->created_by = $user->id;
        });


    }

    protected function filePath(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => !is_null($value) ?  url(Storage::url($value)) : $value
        );
    }


    public function historiable()
    {
        return $this->morphTo();
    }
}
