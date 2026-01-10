<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceSettings extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'timezone',
        'work_week_start',
        'default_project_status',
        'brand_color',
        'logo',
        'working_hours_start',
        'working_hours_end',
        'date_format',
        'currency',
    ];

    protected $casts = [
        'working_hours_start' => 'datetime:H:i',
        'working_hours_end' => 'datetime:H:i',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public static function forTeam(Team $team)
    {
        return static::firstOrCreate(
            ['team_id' => $team->id],
            ['name' => $team->name]
        );
    }
}
