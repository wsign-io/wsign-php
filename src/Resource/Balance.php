<?php

declare(strict_types=1);

namespace Wsign\Resource;

/**
 * Wallet balance and plan (`GET /balance`).
 *
 * @property-read string       $object      `balance`
 * @property-read bool         $livemode
 * @property-read WsignObject $balance     `amount`, `amount_minor`, `currency`
 * @property-read WsignObject $month_spent `amount`, `amount_minor`, `currency`
 * @property-read WsignObject $plan
 */
class Balance extends WsignObject
{
    public const OBJECT_NAME = 'balance';
}
