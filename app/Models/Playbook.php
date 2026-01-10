<?php

namespace App\Models;

use App\Enums\PlaybookType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Playbook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'type',
        'name',
        'description',
        'content',
        'tags',
        'times_applied',
        'last_used',
        'created_by',
        'created_by_name',
        'ai_generated',
    ];

    protected $casts = [
        'content' => 'array',
        'tags' => 'array',
        'last_used' => 'datetime',
        'ai_generated' => 'boolean',
        'type' => PlaybookType::class,
    ];

    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workOrders()
    {
        return $this->belongsToMany(WorkOrder::class)
            ->withPivot(['attached_by', 'attached_at', 'ai_suggested']);
    }

    public function versions()
    {
        return $this->hasMany(PlaybookVersion::class)->orderBy('version_number', 'desc');
    }

    // Scopes
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    // Helper Methods
    public function incrementUsage()
    {
        $this->increment('times_applied');
        $this->update(['last_used' => now()]);
    }

    public function createVersion($modifiedBy, $changeDescription)
    {
        $latestVersion = $this->versions()->first();
        $newVersionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

        return $this->versions()->create([
            'version_number' => $newVersionNumber,
            'content_snapshot' => $this->content,
            'modified_by' => $modifiedBy->id,
            'modified_by_name' => $modifiedBy->name,
            'change_description' => $changeDescription,
            'created_at' => now(),
        ]);
    }
}
