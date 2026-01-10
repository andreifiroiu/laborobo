<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jurager\Teams\Models\Team as BaseTeam;

class Team extends BaseTeam
{
    use HasFactory;
    protected $fillable = [
        'name',
        'user_id',
    ];

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workspaceSettings()
    {
        return $this->hasOne(WorkspaceSettings::class);
    }
}
