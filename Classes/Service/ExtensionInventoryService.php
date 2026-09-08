<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

class ExtensionInventoryService
{
    public function __construct(
        private readonly ListUtility $listUtility,
    ) {
    }

    /**
     * @return array<int, array{name: string, title: string, version: string, active: bool}>
     */
    public function getInstalledExtensions(): array
    {
        $extensions = [];

        foreach ($this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation() as $key => $extension) {
            // Skip TYPO3 system extensions, only report third-party extensions.
            if (($extension['type'] ?? '') !== 'Local') {
                continue;
            }

            $extensions[] = [
                'name' => $key,
                'title' => (string) ($extension['title'] ?? $key),
                'version' => (string) ($extension['version'] ?? '0.0.0'),
                'active' => (bool) ($extension['installed'] ?? false),
            ];
        }

        return $extensions;
    }
}
