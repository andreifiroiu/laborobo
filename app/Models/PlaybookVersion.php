<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaybookVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'playbook_id',
        'version_number',
        'content_snapshot',
        'modified_by',
        'modified_by_name',
        'change_description',
        'created_at',
    ];

    protected $casts = [
        'content_snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function playbook()
    {
        return $this->belongsTo(Playbook::class);
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
