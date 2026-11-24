<?php

declare(strict_types=1);

namespace Wsign\Resource;

/**
 * The API root (`GET /`): account, plan and pricing summary for the key in use.
 *
 * @property-read string       $object   `api_root`
 * @property-read string       $name
 * @property-read string       $version  API version, e.g. `2026-07-01`
 * @property-read bool         $livemode
 * @property-read string       $base_url
 * @property-read WsignObject $account  `id` (`acct_…`), `name`, `email`
 * @property-read WsignObject $plan     `id` (`ap_trial`, `ap_dev`, `ap_biz`, `ap_ent`, `ap_custom`), `paid`, `sends_left`
 * @property-read WsignObject $pricing  `overage_per_request`, `identity_per_signer`, `currency`
 * @property-read string       $docs
 */
class Account extends WsignObject
{
    public const OBJECT_NAME = 'api_root';
}
