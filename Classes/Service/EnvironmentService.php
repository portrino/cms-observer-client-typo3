<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;

class EnvironmentService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    public function getCoreVersion(): string
    {
        return (new Typo3Version())->getVersion();
    }

    public function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    public function getDatabaseVersion(): string
    {
        return (string) $this->connectionPool
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
            ->getServerVersion();
    }
}
