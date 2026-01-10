<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function get(User $user, string $key, $default = null): mixed
    {
        $preference = self::where('user_id', $user->id)
            ->where('key', $key)
            ->first();

        return $preference?->value ?? $default;
    }

    public static function set(User $user, string $key, $value): void
    {
        self::updateOrCreate(
            ['user_id' => $user->id, 'key' => $key],
            ['value' => $value]
        );
    }
}
