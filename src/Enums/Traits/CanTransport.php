<?php

namespace Ninja\DeviceTracker\Enums\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Ninja\DeviceTracker\Contracts\StorableId;

trait CanTransport
{
    public function get(): ?StorableId
    {
        return match ($this) {
            self::Cookie => $this->fromCookie(),
            self::Header => $this->fromHeader(),
            self::Session => $this->fromSession(),
        } ?? $this->fromRequest();
    }

    public static function set(mixed $response, StorableId $id): mixed
    {
        if (! self::isValidResponse($response)) {
            return $response;
        }

        $current = self::current();

        if ($current === null) {
            return $response;
        }

        $callable = match ($current) {
            self::Cookie => function () use ($response, $current, $id): mixed {
                $response->withCookie(
                    Cookie::forever(
                        name: $current->parameter(),
                        value: (string) $id,
                        secure: Config::get('session.secure', false),
                        httpOnly: Config::get('session.http_only', true)
                    )
                );

                return $response;
            },
            self::Header => function () use ($response, $current, $id): mixed {
                $response->header($current->parameter(), (string) $id);

                return $response;
            },
            self::Session => function () use ($response, $current, $id): mixed {
                Session::put($current->parameter(), (string) $id);

                return $response;
            },
        };

        return $callable();
    }

    public static function propagate(?StorableId $id = null): Request
    {
        $current = self::current();

        if ($id === null && $current === null) {
            return request();
        }

        $transportId = $id ?? $current->get();
        if ($transportId === null) {
            return request();
        }

        $requestParameter = self::DEFAULT_REQUEST_PARAMETER;

        return request()->merge([$requestParameter => (string) $transportId]);
    }

    private static function isValidResponse(mixed $response): bool
    {
        $valid = [
            Response::class,
            JsonResponse::class,
        ];

        return in_array(get_class($response), $valid, true);
    }
}
