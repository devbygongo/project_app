<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItemsModel extends Model
{
    use HasFactory;

    protected $table = 't_invoice_items';

    protected $fillable = [
        'invoice_id',
        'product_code',
        'product_name',
        'rate',
        'quantity',
        'total',
        'type',
    ];
}
