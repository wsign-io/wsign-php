<?php

declare(strict_types=1);

namespace Wsign\Service;

use Wsign\RequestOptions;
use Wsign\Resource\Usage;

final class UsageService extends AbstractService
{
    /**
     * Wallet, plan, pricing and request counters (`GET /usage`).
     *
     * @param array<string, mixed>|RequestOptions|null $options
     */
    public function retrieve(array|RequestOptions|null $options = null): Usage
    {
        return $this->requestObject(Usage::class, 'GET', '/usage', [], $options);
    }
}
