<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Photo Model - User Photo Management
 * 
 * AI Model: Claude 4.5 - Multi-AI Orchestration
 * Phase: 4A - Laravel Photo Upload System
 * Purpose: Model for user photo storage and management
 * 
 * Created: 2025-01-19
 * Multi-AI: Serena analysis + Chroma knowledge + Sequential thinking
 */
class Photo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'file_size',
        'width',
        'height',
        'is_primary',
        'is_private',
        'sort_order',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'is_private' => 'boolean',
        'metadata' => 'array',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the user that owns the photo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full URL for the photo.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the full URL for the thumbnail.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail_path) {
            return Storage::url($this->thumbnail_path);
        }
        
        return $this->url;
    }

    /**
     * Scope to get only public photos.
     */
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    /**
     * Scope to get only private photos.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    /**
     * Scope to get photos ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Scope to get the primary photo.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Set this photo as the primary photo for the user.
     */
    public function setAsPrimary(): void
    {
        // Remove primary status from other photos
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);
        
        // Set this photo as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Delete the photo file from storage.
     */
    public function deleteFile(): bool
    {
        $deleted = true;
        
        if ($this->file_path && Storage::exists($this->file_path)) {
            $deleted = Storage::delete($this->file_path) && $deleted;
        }
        
        if ($this->thumbnail_path && Storage::exists($this->thumbnail_path)) {
            $deleted = Storage::delete($this->thumbnail_path) && $deleted;
        }
        
        return $deleted;
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // When deleting a photo, also delete the files
        static::deleting(function ($photo) {
            $photo->deleteFile();
        });
    }
}
