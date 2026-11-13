<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;

class EnvironmentService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getCoreVersion(): string
    {
        return (new Typo3Version())->getVersion();
    }

    public function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * TYPO3 v12 (DBAL 3) prefixes the version with a platform label ("MySQL 10.11.19-MariaDB"),
     * v13+ (DBAL 4) does not; strip it so every TYPO3 version reports the same format.
     */
    public function getDatabaseVersion(): string
    {
        $version = trim($this->getConnection()->getServerVersion());
        $withoutLabel = trim((string)preg_replace('/^(MySQL|PostgreSQL)\s+/i', '', $version));

        return $withoutLabel !== '' ? $withoutLabel : $version;
    }

    /**
     * endoflife.date product slug, null for platforms the Observer doesn't evaluate.
     */
    public function getDatabasePlatform(): ?string
    {
        $platform = $this->getConnection()->getDatabasePlatform();

        // MariaDBPlatform extends MySQLPlatform in DBAL 3, so it must be checked first
        return match (true) {
            $platform instanceof MariaDBPlatform => 'mariadb',
            $platform instanceof AbstractMySQLPlatform => 'mysql',
            $platform instanceof PostgreSQLPlatform => 'postgresql',
            default => null,
        };
    }

    public function getApplicationContext(): string
    {
        return (string)Environment::getContext();
    }

    private function getConnection(): Connection
    {
        return $this->connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
    }
}
