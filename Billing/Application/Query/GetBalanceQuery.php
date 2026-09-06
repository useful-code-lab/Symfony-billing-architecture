<?php

declare(strict_types=1);

namespace Billing\Application\Query;

final readonly class GetBalanceQuery
{
    public function __construct(
        public string $accountId
    ) {}
}
