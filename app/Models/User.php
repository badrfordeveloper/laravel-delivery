<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    use HasRoles,HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function scopeExcludeAdmin(Builder $query)
    {
        return $query->whereNot('users.id', 1);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isManager(): bool
    {
        return $this->isAdmin() || $this->isGestionnaire();
    }

    public function isGestionnaire(): bool
    {
        return $this->hasRole('gestionnaire');
    }

    public function isVendeur(): bool
    {
        return $this->hasRole('vendeur');
    }

    public function isLivreur(): bool
    {
        return $this->hasRole('livreur');
    }

    public function colisLivreur()
    {
        return $this->hasMany(Colis::class, 'livreur_id');
    }


    public function retours()
    {
        return $this->hasMany(Retour::class, 'ramasseur_id');
    }

    public function ramassages()
    {
        return $this->hasMany(Ramassage::class, 'ramasseur_id');
    }

    public function colisVendeur()
    {
        return $this->hasMany(Colis::class, 'vendeur_id');
    }


    public function retoursVendeur()
    {
        return $this->hasMany(Retour::class, 'vendeur_id');
    }

    public function ramassagesVendeur()
    {
        return $this->hasMany(Ramassage::class, 'vendeur_id');
    }
}
