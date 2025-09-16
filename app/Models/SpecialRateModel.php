<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialRateModel extends Model
{
    use HasFactory;

    protected $table = 't_special_rate';

    protected $fillable = [
        'user_id',
        'product_code',
        'rate',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
