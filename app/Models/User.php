<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'name_in_hindi',
        'name_in_telugu',
        'otp',
        'role',
        'is_verified',
        'address_line_1',
        'address_line_2',
        'city',
        'pincode',
        'gstin',
        'state',
        'country',
        'type',
        'app_status',
        // 'category_discount',
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
    public function orders()
    {
        return $this->hasMany(OrderModel::class);
    }

    public function user_cart()
    {
        return $this->hasMany(CartModel::class);
    }

    // One User has many Invoices
    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceModel::class, 'user_id', 'id'); // Reference user_id column in invoices table
    }
}
