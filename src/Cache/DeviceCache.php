<?php

namespace Ninja\DeviceTracker\Cache;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Models\Device;

final class DeviceCache extends AbstractCache
{
    public const KEY_PREFIX = 'device';

    protected function enabled(): bool
    {
        return in_array(self::KEY_PREFIX, Config::get('devices.cache_enabled_for', []));
    }

    protected function forgetItem(Cacheable $item): void
    {
        if (!$this->enabled()) {
            return;
        }

        if (!$item instanceof Device) {
            throw new InvalidArgumentException('Item must be an instance of Device');
        }

        $this->cache->forget($item->key());
        $item->users()->each(fn($user) => $this->cache->forget("user:devices:" . $user->id));
    }

    public static function userDevices(Authenticatable $user)
    {
        if (!self::instance()->enabled()) {
            return $user->devices;
        }

        return self::remember('user:devices:' . $user->id, function () use ($user) {
            return $user->devices;
        });
    }
}
