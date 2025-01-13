<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GodownModel extends Model
{
    use HasFactory;

    protected $table = "t_godown";
    protected $fillable = [
        'name',
        'description',
    ];
}
