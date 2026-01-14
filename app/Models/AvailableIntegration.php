<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailableIntegration extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'icon',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function teamIntegrations()
    {
        return $this->hasMany(TeamIntegration::class);
    }
}
