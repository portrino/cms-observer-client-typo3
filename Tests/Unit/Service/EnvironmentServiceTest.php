<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit\Service;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Portrino\CmsObserverClient\Service\EnvironmentService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class EnvironmentServiceTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function serverVersions(): iterable
    {
        yield 'TYPO3 v12 MariaDB' => ['MySQL 10.11.19-MariaDB-ubu2204-log', '10.11.19-MariaDB-ubu2204-log'];
        yield 'TYPO3 v12 PostgreSQL' => ['PostgreSQL 15.2', '15.2'];
        yield 'TYPO3 v13+ MariaDB' => ['10.11.19-MariaDB-ubu2204-log', '10.11.19-MariaDB-ubu2204-log'];
        yield 'TYPO3 v13+ MySQL' => ['8.0.36', '8.0.36'];
        yield 'label only, nothing to strip to' => ['MySQL', 'MySQL'];
    }

    #[DataProvider('serverVersions')]
    public function testReportsTheDatabaseVersionWithoutThePlatformLabel(string $serverVersion, string $expected): void
    {
        $connection = self::createStub(Connection::class);
        $connection->method('getServerVersion')->willReturn($serverVersion);

        self::assertSame($expected, $this->createService($connection)->getDatabaseVersion());
    }

    /**
     * @return iterable<string, array{AbstractPlatform, string|null}>
     */
    public static function platforms(): iterable
    {
        yield 'MariaDB' => [new MariaDBPlatform(), 'mariadb'];
        yield 'MySQL' => [new MySQLPlatform(), 'mysql'];
        yield 'PostgreSQL' => [new PostgreSQLPlatform(), 'postgresql'];
    }

    #[DataProvider('platforms')]
    public function testReportsTheDatabasePlatform(AbstractPlatform $platform, ?string $expected): void
    {
        $connection = self::createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        self::assertSame($expected, $this->createService($connection)->getDatabasePlatform());
    }

    public function testReportsNoPlatformForOtherDatabases(): void
    {
        $connection = self::createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(self::createStub(AbstractPlatform::class));

        self::assertNull($this->createService($connection)->getDatabasePlatform());
    }

    private function createService(Connection $connection): EnvironmentService
    {
        $connectionPool = self::createStub(ConnectionPool::class);
        $connectionPool->method('getConnectionByName')->willReturn($connection);

        return new EnvironmentService($connectionPool);
    }
}
