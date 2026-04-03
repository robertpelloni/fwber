<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property string $storage_path
 * @property string|null $url
 * @property bool $is_primary
 * @property bool $is_private
 * @property int $blur_level
 * @property bool $is_encrypted
 * @property int $order
 */
class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'storage_path',
        'url',
        'is_primary',
        'is_private',
        'blur_level',
        'is_encrypted',
        'order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_private' => 'boolean',
        'is_encrypted' => 'boolean',
        'blur_level' => 'integer',
        'order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function setAsPrimary(): void
    {
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }
}
