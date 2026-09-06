<?php

declare(strict_types=1);

namespace Billing\Domain\Model;

interface DomainEventInterface
{
    public function getOccurredAt(): \DateTimeImmutable;
}
