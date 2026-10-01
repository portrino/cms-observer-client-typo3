<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

class ExtensionInventoryService
{
    public function __construct(
        private readonly ListUtility $listUtility,
        private readonly PackageManager $packageManager,
    ) {
    }

    /**
     * @return array<int, array{name: string, title: string, version: string, active: bool, composerName: string|null}>
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
                'composerName' => $this->getComposerName($key),
            ];
        }

        return $extensions;
    }

    /**
     * Not every extension ships a composer.json with a "name" (classic
     * ext_emconf.php-only extensions don't), so this is best-effort and
     * reported as null when unavailable.
     */
    private function getComposerName(string $extensionKey): ?string
    {
        if (!$this->packageManager->isPackageAvailable($extensionKey)) {
            return null;
        }

        $composerName = $this->packageManager->getPackage($extensionKey)->getValueFromComposerManifest('name');

        return is_string($composerName) && '' !== $composerName ? $composerName : null;
    }
}
