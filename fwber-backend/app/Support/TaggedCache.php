<?php

namespace App\Support;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

class TaggedCache
{
    public static function remember(array $tags, string $key, \Closure $callback, \DateTimeInterface|int|null $ttl = null): mixed
    {
        if (self::supportsTags()) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember(self::namespacedKey($tags, $key), $ttl, $callback);
    }

    public static function flush(array $tags): void
    {
        if (self::supportsTags()) {
            Cache::tags($tags)->flush();

            return;
        }

        Cache::forever(self::namespaceCacheKey($tags), self::currentNamespaceVersion($tags) + 1);
    }

    private static function supportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }

    private static function namespacedKey(array $tags, string $key): string
    {
        return sprintf(
            'tagged-cache:%s:v%s:%s',
            md5(json_encode(array_values($tags))),
            self::currentNamespaceVersion($tags),
            $key
        );
    }

    private static function namespaceCacheKey(array $tags): string
    {
        return 'tagged-cache:namespace:'.md5(json_encode(array_values($tags)));
    }

    private static function currentNamespaceVersion(array $tags): int
    {
        $namespaceKey = self::namespaceCacheKey($tags);
        $version = Cache::get($namespaceKey);

        if (! is_int($version)) {
            $version = (int) $version ?: 1;
            Cache::forever($namespaceKey, $version);
        }

        return $version;
    }
}
