<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

class ReportBuilder
{
    public function __construct(
        private readonly EnvironmentService $environmentService,
        private readonly ExtensionInventoryService $extensionInventoryService,
    ) {}

    /**
     * "packages" with their "type" needs CMS Observer 0.7.0 or newer.
     *
     * @return array{coreVersion: string, phpVersion: string, dbVersion: string, dbPlatform: string|null, clientVersion: string, applicationContext: string, domains: list<string>, packages: array<int, array{name: string, type: string, title: string, version: string, active: bool, composerName: string|null}>}
     */
    public function build(): array
    {
        return [
            'coreVersion' => $this->environmentService->getCoreVersion(),
            'phpVersion' => $this->environmentService->getPhpVersion(),
            'dbVersion' => $this->environmentService->getDatabaseVersion(),
            'dbPlatform' => $this->environmentService->getDatabasePlatform(),
            'clientVersion' => $this->extensionInventoryService->getVersion('cms_observer_client'),
            'applicationContext' => $this->environmentService->getApplicationContext(),
            'domains' => $this->environmentService->getDomains(),
            'packages' => $this->extensionInventoryService->getInstalledExtensions(),
        ];
    }
}
