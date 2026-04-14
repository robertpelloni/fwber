<?php

namespace App\Support;

use BadMethodCallException;
use Illuminate\Support\Facades\Cache;

class TaggedCache
{
    public static function remember(array $tags, string $key, \Closure $callback, \DateTimeInterface|int|null $ttl = null): mixed
    {
        try {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        } catch (BadMethodCallException|\Throwable) {
            // The rewind branch mixes modern tagged-cache expectations with older
            // environments and test doubles. Always attempt tag usage first so
            // Cache::shouldReceive('tags') expectations remain valid in tests,
            // then fall back when the runtime/store/mock cannot actually support
            // tags.
        }

        return Cache::remember(self::namespacedKey($tags, $key), $ttl, $callback);
    }

    public static function flush(array $tags): void
    {
        try {
            Cache::tags($tags)->flush();

            return;
        } catch (BadMethodCallException|\Throwable) {
            // Keep the fallback invalidation path below for stores, mocks, or
            // environments that reject tag operations at runtime.
        }

        Cache::forever(self::namespaceCacheKey($tags), self::currentNamespaceVersion($tags) + 1);
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
