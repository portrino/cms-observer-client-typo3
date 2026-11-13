<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Portrino\CmsObserverClient\Command\PushReportCommand;
use Portrino\CmsObserverClient\Service\EnvironmentService;
use Portrino\CmsObserverClient\Service\ExtensionInventoryService;
use Portrino\CmsObserverClient\Service\ObserverApiClient;
use Portrino\CmsObserverClient\Service\ReportBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class PushReportCommandTest extends TestCase
{
    public function testDryRunPrintsTheReportWithoutSendingIt(): void
    {
        $apiClient = $this->createMock(ObserverApiClient::class);
        $apiClient->expects($this->never())->method('pushReport');

        $tester = new CommandTester(new PushReportCommand($this->createReportBuilder(), $apiClient));

        self::assertSame(Command::SUCCESS, $tester->execute(['--dry-run' => true]));
        self::assertSame([
            'coreVersion' => '13.4.0',
            'phpVersion' => '8.3.0',
            'dbVersion' => '11.8.0',
            'clientVersion' => '1.0.0',
            'applicationContext' => 'Production/Staging',
            'extensions' => [['name' => 'news', 'title' => 'News', 'version' => '12.0.0', 'active' => true, 'composerName' => 'georgringer/news']],
        ], json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testPushesTheReport(): void
    {
        $apiClient = $this->createMock(ObserverApiClient::class);
        $apiClient->expects($this->once())->method('pushReport')
            ->with(self::callback(static fn(array $report) => $report['clientVersion'] === '1.0.0'))
            ->willReturn(true);

        $tester = new CommandTester(new PushReportCommand($this->createReportBuilder(), $apiClient));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('1 extensions', $tester->getDisplay());
    }

    public function testFailsWhenTheObserverRejectsTheReport(): void
    {
        $apiClient = self::createStub(ObserverApiClient::class);
        $apiClient->method('pushReport')->willReturn(false);

        $tester = new CommandTester(new PushReportCommand($this->createReportBuilder(), $apiClient));

        self::assertSame(Command::FAILURE, $tester->execute([]));
    }

    private function createReportBuilder(): ReportBuilder
    {
        $environment = self::createStub(EnvironmentService::class);
        $environment->method('getCoreVersion')->willReturn('13.4.0');
        $environment->method('getPhpVersion')->willReturn('8.3.0');
        $environment->method('getDatabaseVersion')->willReturn('11.8.0');
        $environment->method('getApplicationContext')->willReturn('Production/Staging');

        $inventory = self::createStub(ExtensionInventoryService::class);
        $inventory->method('getVersion')->willReturn('1.0.0');
        $inventory->method('getInstalledExtensions')->willReturn([
            ['name' => 'news', 'title' => 'News', 'version' => '12.0.0', 'active' => true, 'composerName' => 'georgringer/news'],
        ]);

        return new ReportBuilder($environment, $inventory);
    }
}
