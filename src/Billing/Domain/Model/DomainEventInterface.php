<?php

declare(strict_types=1);

namespace App\Billing\Domain\Model;

interface DomainEventInterface
{
    public function getOccurredAt(): \DateTimeImmutable;
}
