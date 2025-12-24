<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Log;

trait SafelyHydratesAttributes
{
    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        try {
            return parent::getAttribute($key);
        } catch (\Throwable $e) {
            // Log the error but don't crash the app
            Log::error("SafelyHydratesAttributes: Error accessing attribute '$key' on " . static::class . ": " . $e->getMessage());
            return null;
        }
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        try {
            return parent::toArray();
        } catch (\Throwable $e) {
            Log::error("SafelyHydratesAttributes: Error converting " . static::class . " to array: " . $e->getMessage());
            
            // Return a safe subset or empty array
            return [
                'id' => $this->id ?? null,
                'error' => 'Model serialization failed',
            ];
        }
    }

    /**
     * Get the attributes that have been changed since the last sync.
     *
     * @return array
     */
    public function getDirty()
    {
        try {
            return parent::getDirty();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
