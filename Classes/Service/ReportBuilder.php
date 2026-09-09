<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

class ReportBuilder
{
    public function __construct(
        private readonly EnvironmentService $environmentService,
        private readonly ExtensionInventoryService $extensionInventoryService,
    ) {
    }

    /**
     * @return array{coreVersion: string, phpVersion: string, dbVersion: string, extensions: array<int, array{name: string, title: string, version: string, active: bool, composerName: string|null}>}
     */
    public function build(): array
    {
        return [
            'coreVersion' => $this->environmentService->getCoreVersion(),
            'phpVersion' => $this->environmentService->getPhpVersion(),
            'dbVersion' => $this->environmentService->getDatabaseVersion(),
            'extensions' => $this->extensionInventoryService->getInstalledExtensions(),
        ];
    }
}
