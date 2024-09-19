<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // Each Invoice belongs to one User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
