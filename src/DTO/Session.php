<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Modules\Location\DTO\Location;
use Stringable;

final readonly class Session implements JsonSerializable, Stringable
{
    public function __construct(
        public string $uuid,
        public string $ip,
        public Location $location,
        public SessionStatus $status,
        public string $lastActivityAt,
        public string $startedAt,
        public ?string $finishedAt,
        public Device $device
    ) {
    }

    public static function fromModel(\Ninja\DeviceTracker\Models\Session $session): self
    {
        return new self(
            uuid: $session->uuid->toString(),
            ip: $session->ip,
            location: $session->location,
            status: $session->status,
            lastActivityAt: $session->last_activity_at,
            startedAt: $session->started_at,
            finishedAt: $session->finished_at,
            device: Device::fromModel($session->device)
        );
    }

    public function array(): array
    {
        return [
            "uuid" => $this->uuid,
            "ip" => $this->ip,
            "location" => $this->location->array(),
            "status" => $this->status->value,
            "lastActivityAt" => $this->lastActivityAt,
            "startedAt" => $this->startedAt,
            "finishedAt" => $this->finishedAt,
            "device" => $this->device->array(),
            "label" => (string) $this
        ];
    }

    public function json(): string
    {
        return json_encode($this->array());
    }

    public function __toString(): string
    {
        return sprintf("%s %s %s", $this->ip, $this->location, $this->lastActivityAt);
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }
}
