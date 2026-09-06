<?php
declare(strict_types=1);

namespace App\Billing\Domain\Repository;

use App\Billing\Domain\Model\Account;

interface AccountRepositoryInterface
{
    public function getById(string $id): ?Account;

    public function save(Account $account): void;
}
