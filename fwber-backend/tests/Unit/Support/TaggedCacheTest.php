<?php

namespace Tests\Unit\Support;

use App\Support\TaggedCache;
use BadMethodCallException;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TaggedCacheTest extends TestCase
{
    public function test_remember_falls_back_when_runtime_tagging_is_rejected(): void
    {
        Cache::shouldReceive('getStore')
            ->once()
            ->andReturn(new class extends \Illuminate\Cache\TaggableStore {
                public function many(array $keys): array
                {
                    return [];
                }

                public function putMany(array $values, $seconds): bool
                {
                    return true;
                }

                public function increment($key, $value = 1): int
                {
                    return $value;
                }

                public function decrement($key, $value = 1): int
                {
                    return $value;
                }

                public function forever($key, $value): bool
                {
                    return true;
                }

                public function forget($key): bool
                {
                    return true;
                }

                public function flush(): bool
                {
                    return true;
                }

                public function getPrefix(): string
                {
                    return 'test:';
                }

                public function get($key): mixed
                {
                    return null;
                }

                public function put($key, $value, $seconds): bool
                {
                    return true;
                }
            });

        Cache::shouldReceive('tags')
            ->once()
            ->with(['matches_feed:user_1'])
            ->andThrow(new BadMethodCallException('This cache store does not support tagging.'));

        Cache::shouldReceive('get')
            ->once()
            ->with(\Mockery::on(fn (string $key) => str_starts_with($key, 'tagged-cache:namespace:')))
            ->andReturn(null);

        Cache::shouldReceive('forever')
            ->once()
            ->with(\Mockery::on(fn (string $key) => str_starts_with($key, 'tagged-cache:namespace:')), 1);

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function (string $key, int $ttl, \Closure $callback): bool {
                $this->assertStringStartsWith('tagged-cache:', $key);
                $this->assertSame(300, $ttl);

                return true;
            })
            ->andReturnUsing(fn (string $key, int $ttl, \Closure $callback) => $callback());

        $value = TaggedCache::remember(['matches_feed:user_1'], 'feed:user_1:test', fn () => 'cached', 300);

        $this->assertSame('cached', $value);
    }

    public function test_flush_falls_back_when_runtime_tagging_is_rejected(): void
    {
        Cache::shouldReceive('getStore')
            ->once()
            ->andReturn(new class extends \Illuminate\Cache\TaggableStore {
                public function many(array $keys): array
                {
                    return [];
                }

                public function putMany(array $values, $seconds): bool
                {
                    return true;
                }

                public function increment($key, $value = 1): int
                {
                    return $value;
                }

                public function decrement($key, $value = 1): int
                {
                    return $value;
                }

                public function forever($key, $value): bool
                {
                    return true;
                }

                public function forget($key): bool
                {
                    return true;
                }

                public function flush(): bool
                {
                    return true;
                }

                public function getPrefix(): string
                {
                    return 'test:';
                }

                public function get($key): mixed
                {
                    return null;
                }

                public function put($key, $value, $seconds): bool
                {
                    return true;
                }
            });

        Cache::shouldReceive('tags')
            ->once()
            ->with(['matches_feed:user_1'])
            ->andThrow(new BadMethodCallException('This cache store does not support tagging.'));

        Cache::shouldReceive('get')
            ->once()
            ->with(\Mockery::on(fn (string $key) => str_starts_with($key, 'tagged-cache:namespace:')))
            ->andReturn(2);

        Cache::shouldReceive('forever')
            ->once()
            ->with(\Mockery::on(fn (string $key) => str_starts_with($key, 'tagged-cache:namespace:')), 3);

        TaggedCache::flush(['matches_feed:user_1']);

        $this->assertTrue(true);
    }
}
