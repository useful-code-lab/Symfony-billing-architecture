<?php

declare(strict_types=1);

namespace App\Billing\Domain\Model;

final readonly class AccountDepositedEvent implements DomainEventInterface
{
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        public string $accountId,
        public int $amount,
        public string $currency
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
