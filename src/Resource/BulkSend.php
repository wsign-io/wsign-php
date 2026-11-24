<?php

declare(strict_types=1);

namespace Wsign\Resource;

/**
 * Result of a bulk send: one signature request per recipient.
 *
 * @property-read string             $object   `bulk_send`
 * @property-read int                $created
 * @property-read int                $failed
 * @property-read list<WsignObject> $requests Each has `signature_request`, `email`, `phone`, `status`
 * @property-read list<WsignObject> $errors   Each has `email`, `phone`, `error`, `code`
 */
class BulkSend extends WsignObject
{
    public const OBJECT_NAME = 'bulk_send';
}
