<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

use Composer\InstalledVersions;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

class ExtensionInventoryService
{
    public function __construct(
        private readonly PackageManager $packageManager,
    ) {}

    /**
     * @return array<int, array{name: string, title: string, version: string, active: bool, composerName: string|null}>
     */
    public function getInstalledExtensions(): array
    {
        $extensions = [];

        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $metaData = $package->getPackageMetaData();
            // Skip TYPO3 system extensions, only report third-party extensions.
            if (!$metaData->isExtensionType() || $metaData->isFrameworkType()) {
                continue;
            }

            $key = $package->getPackageKey();
            $extensions[] = [
                'name' => $key,
                'title' => $metaData->getTitle() ?? $key,
                'version' => $this->resolveVersion($package),
                'active' => $this->packageManager->isPackageActive($key),
                'composerName' => $this->getComposerName($package),
            ];
        }

        return $extensions;
    }

    public function getVersion(string $extensionKey): string
    {
        return $this->resolveVersion($this->packageManager->getPackage($extensionKey));
    }

    /**
     * In Composer mode the installed version is authoritative: ext_emconf.php may be
     * stale, and TYPO3 v14 reports "1.0.0+no-version-set" when composer.json has none.
     */
    private function resolveVersion(PackageInterface $package): string
    {
        $composerName = $this->getComposerName($package);
        $installedVersion = $composerName !== null && InstalledVersions::isInstalled($composerName)
            ? InstalledVersions::getPrettyVersion($composerName)
            : null;
        // v12/v13 report an empty string for extensions without any version
        $metaDataVersion = $package->getPackageMetaData()->getVersion();

        return $installedVersion ?? ($metaDataVersion !== '' ? $metaDataVersion : '0.0.0');
    }

    /**
     * Not every extension ships a composer.json with a "name" (classic
     * ext_emconf.php-only extensions don't), so this is best-effort and
     * reported as null when unavailable.
     */
    private function getComposerName(PackageInterface $package): ?string
    {
        $composerName = $package->getValueFromComposerManifest('name');

        return is_string($composerName) && $composerName !== '' ? $composerName : null;
    }
}
