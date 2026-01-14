<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingInfo extends Model
{
    protected $table = 'billing_info';

    protected $fillable = [
        'team_id',
        'plan_name',
        'plan_price',
        'billing_cycle',
        'billing_period_start',
        'billing_period_end',
        'next_billing_date',
        'users_included',
        'users_current',
        'projects_included',
        'projects_current',
        'storage_gb_included',
        'storage_gb_current',
        'ai_requests_included',
        'ai_requests_current',
        'payment_method',
        'card_brand',
        'card_last4',
        'card_expiry',
        'status',
        'trial_ends_at',
    ];

    protected $casts = [
        'plan_price' => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'next_billing_date' => 'date',
        'storage_gb_included' => 'decimal:2',
        'storage_gb_current' => 'decimal:2',
        'card_expiry' => 'date',
        'trial_ends_at' => 'date',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public static function forTeam(Team $team)
    {
        return static::firstOrCreate(['team_id' => $team->id]);
    }
}
