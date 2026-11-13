<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit\Service;

use Composer\InstalledVersions;
use PHPUnit\Framework\TestCase;
use Portrino\CmsObserverClient\Service\ExtensionInventoryService;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

class ExtensionInventoryServiceTest extends TestCase
{
    public function testReportsThirdPartyExtensionsOnly(): void
    {
        $service = $this->createService([
            $this->package('core', 'typo3-cms-framework', '13.4.0', 'typo3/cms-core'),
            $this->package('news', 'typo3-cms-extension', '12.0.0', null, 'News'),
            $this->package('some_library', 'library', '1.0.0', 'vendor/some-library'),
        ], ['news']);

        self::assertSame([
            ['name' => 'news', 'title' => 'News', 'version' => '12.0.0', 'active' => true, 'composerName' => null],
        ], $service->getInstalledExtensions());
    }

    public function testPrefersTheComposerInstalledVersionOverTheMetaDataVersion(): void
    {
        // phpunit/phpunit stands in for any Composer-installed extension here
        $service = $this->createService([
            $this->package('news', 'typo3-cms-extension', '1.0.0+no-version-set', 'phpunit/phpunit'),
        ], ['news']);

        $extension = $service->getInstalledExtensions()[0];
        self::assertSame(InstalledVersions::getPrettyVersion('phpunit/phpunit'), $extension['version']);
        self::assertSame('phpunit/phpunit', $extension['composerName']);
    }

    public function testFallsBackToTheMetaDataVersionWhenNotInstalledViaComposer(): void
    {
        $service = $this->createService([
            $this->package('classic', 'typo3-cms-extension', '2.3.4', 'vendor/not-installed'),
        ], []);

        $extension = $service->getInstalledExtensions()[0];
        self::assertSame('2.3.4', $extension['version']);
        self::assertFalse($extension['active']);
        self::assertSame('classic', $extension['title']);
    }

    public function testReportsAnEmptyMetaDataVersionAsZero(): void
    {
        if ((new Typo3Version())->getMajorVersion() >= 14) {
            self::markTestSkipped('TYPO3 v14 rejects an empty package version, so the case only exists on v12/v13.');
        }
        $service = $this->createService([
            $this->package('classic', 'typo3-cms-extension', '', 'vendor/not-installed'),
        ], []);

        self::assertSame('0.0.0', $service->getInstalledExtensions()[0]['version']);
    }

    public function testGetVersionResolvesASinglePackage(): void
    {
        $service = $this->createService([
            $this->package('cms_observer_client', 'typo3-cms-extension', '1.2.0'),
        ], ['cms_observer_client']);

        self::assertSame('1.2.0', $service->getVersion('cms_observer_client'));
    }

    /**
     * @param PackageInterface[] $packages
     * @param string[]           $activeKeys
     */
    private function createService(array $packages, array $activeKeys): ExtensionInventoryService
    {
        $byKey = [];
        foreach ($packages as $package) {
            $byKey[$package->getPackageKey()] = $package;
        }

        $packageManager = self::createStub(PackageManager::class);
        $packageManager->method('getAvailablePackages')->willReturn($byKey);
        $packageManager->method('getPackage')->willReturnCallback(static fn(string $key) => $byKey[$key]);
        $packageManager->method('isPackageActive')->willReturnCallback(static fn(string $key) => in_array($key, $activeKeys, true));

        return new ExtensionInventoryService($packageManager);
    }

    private function package(string $key, string $type, string $version, ?string $composerName = null, ?string $title = null): PackageInterface
    {
        $metaData = new MetaData($key);
        $metaData->setPackageType($type);
        $metaData->setVersion($version);
        $metaData->setTitle($title);

        $package = self::createStub(PackageInterface::class);
        $package->method('getPackageKey')->willReturn($key);
        $package->method('getPackageMetaData')->willReturn($metaData);
        $package->method('getValueFromComposerManifest')->willReturnCallback(
            static fn(?string $field = null) => $field === 'name' ? $composerName : null
        );

        return $package;
    }
}
