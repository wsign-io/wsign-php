<?php

declare(strict_types=1);

namespace Wsign\Resource;

/**
 * Result of asking the platform to deliver an event again.
 *
 * @property-read string             $object     `event_redelivery`
 * @property-read string             $event      `evt_…`
 * @property-read list<WsignObject> $deliveries Each has `endpoint`, `status`, `response_code`, `error`
 */
class EventRedelivery extends WsignObject
{
    public const OBJECT_NAME = 'event_redelivery';
}
