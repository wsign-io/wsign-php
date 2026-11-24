<?php

declare(strict_types=1);

namespace Wsign\Service;

use Wsign\RequestOptions;
use Wsign\Resource\Balance;

final class BalanceService extends AbstractService
{
    /**
     * Wallet balance and plan (`GET /balance`).
     *
     * @param array<string, mixed>|RequestOptions|null $options
     */
    public function retrieve(array|RequestOptions|null $options = null): Balance
    {
        return $this->requestObject(Balance::class, 'GET', '/balance', [], $options);
    }
}
