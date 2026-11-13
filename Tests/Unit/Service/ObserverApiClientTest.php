<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Portrino\CmsObserverClient\Service\ObserverApiClient;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

class ObserverApiClientTest extends TestCase
{
    /** @var array{0: string, 1: string, 2: array<string, mixed>}|null */
    private ?array $sentRequest = null;

    protected function tearDown(): void
    {
        putenv('CMS_OBSERVER_API_URL');
        putenv('CMS_OBSERVER_TOKEN');
    }

    public function testSendsTheReportFromTheExtensionConfiguration(): void
    {
        $client = $this->createClient(['apiUrl' => 'https://observer.test/', 'token' => ' secret ']);

        self::assertTrue($client->pushReport(['coreVersion' => '13.4.0']));

        [$url, $method, $options] = $this->sentRequest;
        self::assertSame('https://observer.test/api/v1/sites/report', $url);
        self::assertSame('POST', $method);
        self::assertSame('Bearer secret', $options['headers']['Authorization']);
        self::assertSame('{"coreVersion":"13.4.0"}', $options['body']);
        self::assertGreaterThan(0, $options['timeout']);
    }

    public function testEnvironmentVariablesWinOverTheExtensionConfiguration(): void
    {
        putenv('CMS_OBSERVER_API_URL=https://env.test');
        putenv('CMS_OBSERVER_TOKEN=env-token');
        $client = $this->createClient(['apiUrl' => 'https://observer.test', 'token' => 'secret']);

        $client->pushReport([]);

        self::assertSame('https://env.test/api/v1/sites/report', $this->sentRequest[0]);
        self::assertSame('Bearer env-token', $this->sentRequest[2]['headers']['Authorization']);
    }

    public function testWorksWithEnvironmentVariablesWhenTheExtensionIsNotConfigured(): void
    {
        putenv('CMS_OBSERVER_API_URL=https://env.test');
        putenv('CMS_OBSERVER_TOKEN=env-token');
        $client = $this->createClient(null);

        self::assertTrue($client->pushReport([]));
    }

    public function testThrowsWhenNothingIsConfigured(): void
    {
        $client = $this->createClient(['apiUrl' => '', 'token' => '']);

        $this->expectExceptionCode(1757337600);
        $client->pushReport([]);
    }

    public function testReturnsFalseWhenTheObserverDoesNotAccept(): void
    {
        $client = $this->createClient(['apiUrl' => 'https://observer.test', 'token' => 'secret'], 200);

        self::assertFalse($client->pushReport([]));
    }

    /**
     * @param array<string, string>|null $config null = extension not configured at all
     */
    private function createClient(?array $config, int $statusCode = 202): ObserverApiClient
    {
        $response = self::createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);

        $requestFactory = self::createStub(RequestFactory::class);
        $requestFactory->method('request')->willReturnCallback(function (string $url, string $method, array $options) use ($response) {
            $this->sentRequest = [$url, $method, $options];

            return $response;
        });

        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        if ($config === null) {
            $extensionConfiguration->method('get')->willThrowException(new ExtensionConfigurationExtensionNotConfiguredException());
        } else {
            $extensionConfiguration->method('get')->willReturn($config);
        }

        return new ObserverApiClient($requestFactory, $extensionConfiguration);
    }
}
