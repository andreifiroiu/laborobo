<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'team_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'amount',
        'status',
        'paid_at',
        'description',
        'pdf_url',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
