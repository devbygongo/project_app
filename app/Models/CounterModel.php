<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterModel extends Model
{
    use HasFactory;

    protected $table = 't_counter';

    protected $fillable = [
        'name',
        'prefix',
        'counter',
        'postfix',
    ];
}
