<?php

namespace Ninja\DeviceTracker\Cache;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Models\Device;
use Psr\SimpleCache\InvalidArgumentException;

/** @phpstan-consistent-constructor */
abstract class AbstractCache
{
    public const KEY_PREFIX = '';

    /** @var static[] */
    protected static array $instances = [];

    protected ?Repository $cache = null;

    private function __construct()
    {
        if (! $this->enabled()) {
            return;
        }

        /** @var string $store */
        $store = config('devices.cache_store');
        $this->cache = Cache::store($store);
    }

    public static function instance(): self
    {
        if (! isset(self::$instances[static::class])) {
            self::$instances[static::class] = new static;
        }

        return self::$instances[static::class];
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function get(string $key): ?Device
    {
        return self::instance()->getItem($key);
    }

    public static function put(Cacheable $item): void
    {
        self::instance()->putItem($item);
    }

    public static function remember(string $key, Closure $callback): mixed
    {
        if (! self::instance()->enabled()) {
            return $callback();
        }

        return self::instance()->cache?->remember($key, self::instance()->ttl(), $callback);
    }

    public static function key(string $key): string
    {
        return sprintf('%s:%s', static::KEY_PREFIX, hash('xxh128', $key));
    }

    public static function forget(Cacheable $item): void
    {
        self::instance()->forgetItem($item);
    }

    public static function flush(): void
    {
        if (! self::instance()->enabled()) {
            return;
        }

        if (! is_null(self::instance()->cache) && method_exists(self::instance()->cache, 'flush')) {
            self::instance()->cache->flush();
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getItem(string $key): ?Device
    {
        if (! $this->enabled()) {
            return null;
        }

        return $this->cache?->get($key);
    }

    protected function putItem(Cacheable $item): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->cache?->put($item->key(), $item, $item->ttl() ?? $this->ttl());
    }

    protected function forgetItem(Cacheable $item): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->cache?->forget($item->key());
    }

    protected function ttl(): int
    {
        return Config::get('devices.cache_ttl')[static::KEY_PREFIX];
    }

    abstract protected function enabled(): bool;
}
