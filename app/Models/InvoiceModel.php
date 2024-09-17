<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceModel extends Model
{
    use HasFactory;

    protected $table = 't_invoice';

    protected $fillable = [
        'user_id',
        'order_id',
        'invoice_number',
        'date',
        'amount',
        'type',
        'type',
    ];
}
