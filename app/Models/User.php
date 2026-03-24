<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'role',
        'avatarUrl',
        'phone',
        'country',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function paniers()
    {
        return $this->hasMany(Panier::class, 'acheteur_id');
    }

    public function commandesAchetees()
    {
        return $this->hasMany(Commande::class, 'acheteur_id');
    }

    public function commandesVendues()
    {
        return $this->hasMany(Commande::class, 'vendeur_id');
    }

    public function transactionsAcheteur()
    {
        return $this->hasMany(Transaction::class, 'acheteur_id');
    }

    public function transactionsVendeur()
    {
        return $this->hasMany(Transaction::class, 'vendeur_id');
    }

    public function avis()
    {
        return $this->hasMany(Avis::class);
    }

    public function favoris()
    {
        return $this->hasMany(Favori::class);
    }

    public function litigesInitie()
    {
        return $this->hasMany(Litige::class, 'initiateur_id');
    }

    public function litigesGeres()
    {
        return $this->hasMany(Litige::class, 'admin_id');
    }
}
