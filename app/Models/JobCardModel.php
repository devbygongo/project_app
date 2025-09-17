<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCardModel extends Model
{
    use HasFactory;

    protected $table = 't_job_card';

    protected $fillable = [
        'client_name',
        'job_id',               // auto-generated in store()
        'mobile',
        'warranty',
        'serial_no',
        'model_no',
        'problem_description',
        'assigned_to',               // varchar, nullable
    ];
}
