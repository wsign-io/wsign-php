<?php

declare(strict_types=1);

namespace Wsign\Resource;

/**
 * @property-read string $object            `reminder`
 * @property-read string $signature_request `sr_…`
 * @property-read int    $reminders_sent    Number of signers reminded
 */
class Reminder extends WsignObject
{
    public const OBJECT_NAME = 'reminder';
}
