<?php

declare(strict_types=1);

namespace App\Tests\Billing\Infrastructure\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class DepositApiTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Инициализируем клиент (поднимает ядро Symfony Kernel)
        self::createClient();

        // 2. Достаем EntityManager из тестового контейнера, чтобы подготовить/очистить БД
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        // Перед каждым тестом принудительно очищаем таблицу аккаунтов, чтобы тесты были изолированы
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE billing_accounts CASCADE');
    }

    /**
     * Тест проверяет успешный цикл: 
     * Создание аккаунта напрямую -> HTTP POST запрос на пополнение -> Проверка баланса через HTTP GET
     */
    public function testItDepositsMoneyAndReflectsOnBalance(): void
    {
        $client = self::getClient();
        $accountId = '2c21966a-b2be-4977-bc60-d26b528a475d'; // Валидный UUID v4

        // 1. Имитируем, что аккаунт уже существует в базе (создаем его через сырой SQL для скорости теста)
        $this->entityManager->getConnection()->executeStatement('
            INSERT INTO billing_accounts (id, user_id, balance_amount, balance_currency) 
            VALUES (:id, :user_id, :amount, :currency)
        ', [
            'id' => $accountId,
            'user_id' => 'user-123',
            'amount' => 1000, // Начальный баланс 10.00 USD (в центах)
            'currency' => 'USD'
        ]);

        // 2. Выполняем HTTP POST запрос на пополнение баланса еще на 5.00 USD
        $client->request(
            method: 'POST',
            uri: sprintf('/api/v1/accounts/%s/deposit', $accountId),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'amount' => 500, // 5.00 USD в центах
                'currency' => 'USD'
            ], JSON_THROW_ON_ERROR)
        );

        // Проверяем, что контроллер вернул семантически верный статус 202 Accepted (Шаг 5)
        $this->assertSame(Response::HTTP_ACCEPTED, $client->getResponse()->getStatusCode());

        // 3. Выполняем HTTP GET запрос на чтение баланса, проверяя работу нашего DBAL-слоя (Шаг 11)
        $client->request(method: 'GET', uri: sprintf('/api/v1/accounts/%s/balance', $accountId));
        
        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        
        // Парсим JSON ответа
        $responseData = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Проверяем, что баланс стал равен 15.00 USD (10.00 начальных + 5.00 пополнения)
        $this->assertSame($accountId, $responseData['account_id']);
        $this->assertEquals(15.00, $responseData['balance']);
        $this->assertSame('USD', $responseData['currency']);
    }

    /**
     * Тест проверяет работу Middleware Валидации (Шаг 9) и глобального ExceptionListener (Шаг 6)
     */
    public function testItReturns422WhenInputDataIsInvalid(): void
    {
        $client = self::getClient();
        $invalidAccountId = 'not-a-valid-uuid';

        // Отправляем заведомо некорректные данные (битый UUID, отрицательная сумма, некорректная валюта)
        $client->request(
            method: 'POST',
            uri: sprintf('/api/v1/accounts/%s/deposit', $invalidAccountId),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'amount' => -100, // Отрицательная сумма запрещена
                'currency' => 'INVALID_CURRENCY' // Ожидается ровно 3 символа
            ], JSON_THROW_ON_ERROR)
        );

        // Проверяем, что Middleware перехватило ошибку и выдало статус 422 Unprocessable Entity
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Проверяем структурированный ответ нарушений, который мы настроили в ExceptionListener
        $this->assertArrayHasKey('violations', $responseData['error']);
        $this->assertArrayHasKey('accountId', $responseData['error']['violations']);
        $this->assertArrayHasKey('amountInCents', $responseData['error']['violations']);
        $this->assertArrayHasKey('currency', $responseData['error']['violations']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
