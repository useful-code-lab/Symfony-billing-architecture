<?php


declare(strict_types=1);

namespace App\Billing\Infrastructure\Console;

use App\Billing\Application\Command\DepositMoneyCommand as ApplicationDepositCommand;
use App\Billing\Domain\Model\Account;
use App\Billing\Domain\Model\Money;
use App\Billing\Domain\Repository\AccountRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:billing:deposit',
    description: 'Ручное пополнение баланса аккаунта через консоль администратора.'
)]
final class DepositMoneyCommand extends Command
{
    // Внедряем шину команд И репозиторий для проверки/автосоздания
    public function __construct(
        private MessageBusInterface $commandBus,
        private AccountRepositoryInterface $accountRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('accountId', InputArgument::REQUIRED, 'UUID аккаунта')
            ->addArgument('amount', InputArgument::REQUIRED, 'Сумма пополнения (например, 10.50)')
            ->addArgument('currency', InputArgument::OPTIONAL, 'Код валюты (ISO 4217)', 'USD');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $accountId = (string) $input->getArgument('accountId');
        $amountFloat = (float) $input->getArgument('amount');
        $currency = strtoupper((string) $input->getArgument('currency'));

        if ($amountFloat <= 0) {
            $io->error('Сумма пополнения должна быть больше нуля.');
            return Command::INVALID;
        }

        $amountInCents = (int) round($amountFloat * 100);

        try {
            // АВТОСОЗДАНИЕ: Если аккаунта нет в PostgreSQL, создаем его с нулевым балансом
            $account = $this->accountRepository->getById($accountId);
            if ($account === null) {
                $io->comment('Аккаунт не найден. Автоматически создаем новый профиль...');
                
                $zeroBalance = new Money(0, $currency);
                $newAccount = new Account($accountId, 'user-auto-generated', $zeroBalance);
                
                $this->accountRepository->save($newAccount);
            }

            // Теперь гарантированно отправляем команду пополнения в шину
            $command = new ApplicationDepositCommand(
                accountId: $accountId,
                amountInCents: $amountInCents,
                currency: $currency
            );

            $this->commandBus->dispatch($command);

            $io->success(sprintf(
                'Баланс аккаунта %s успешно пополнен на %.2f %s.',
                $accountId,
                $amountFloat,
                $currency
            ));

            return Command::SUCCESS;

        } catch (\Throwable $exception) {
            $io->error(sprintf('Не удалось пополнить баланс: %s', $exception->getMessage()));
            return Command::FAILURE;
        }
    }
}
