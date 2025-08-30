<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogsModel extends Model
{
    use HasFactory;

    protected $table = 't_request_json';   // Explicitly set the table name

    protected $primaryKey = 'id';          // Primary key

    public $timestamps = false;            // Disable if table doesn’t have updated_at / created_at handled by Laravel

    protected $fillable = [
        'function',
        'request',
        'created_at',
    ];
}
