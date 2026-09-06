<?php
declare(strict_types=1);

namespace Billing\Domain\Repository;

use Billing\Domain\Model\Account;

interface AccountRepositoryInterface
{
    public function getById(string $id): ?Account;

    public function save(Account $account): void;
}
