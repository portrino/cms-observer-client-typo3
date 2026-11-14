<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Portrino\CmsObserverClient\Service\ObserverApiClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
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

        $client->pushReport(['coreVersion' => '13.4.0']);

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

        $client->pushReport([]);
        self::assertNotNull($this->sentRequest);
    }

    public function testThrowsWhenNothingIsConfigured(): void
    {
        $client = $this->createClient(['apiUrl' => '', 'token' => '']);

        $this->expectExceptionCode(1757337600);
        $client->pushReport([]);
    }

    public function testRefusesToSendTheTokenOverPlainHttp(): void
    {
        putenv('CMS_OBSERVER_API_URL=http://observer.test');
        $client = $this->createClient(['apiUrl' => 'https://observer.test', 'token' => 'secret']);

        try {
            $client->pushReport([]);
            self::fail('Expected an exception for a non-https API URL');
        } catch (\RuntimeException $e) {
            self::assertSame(1757337601, $e->getCode());
        }
        self::assertNull($this->sentRequest);
    }

    public function testThrowsWithTheObserversMessageWhenTheReportIsRejected(): void
    {
        $client = $this->createClient(['apiUrl' => 'https://observer.test', 'token' => 'secret'], 409, '{"status":"rejected","reason":"domain_mismatch","message":"Report from staging.example.com does not match the site URL https://example.com"}');

        $this->expectExceptionCode(1757337602);
        $this->expectExceptionMessage('CMS Observer rejected the report (HTTP 409): Report from staging.example.com does not match the site URL https://example.com');
        $client->pushReport([]);
    }

    public function testThrowsWithTheReasonPhraseWhenTheRejectionHasNoMessage(): void
    {
        $client = $this->createClient(['apiUrl' => 'https://observer.test', 'token' => 'secret'], 200, '<html>OK</html>');

        $this->expectExceptionMessage('CMS Observer rejected the report (HTTP 200): Reason');
        $client->pushReport([]);
    }

    /**
     * @param array<string, string>|null $config null = extension not configured at all
     */
    private function createClient(?array $config, int $statusCode = 202, string $body = ''): ObserverApiClient
    {
        $response = self::createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getReasonPhrase')->willReturn('Reason');
        $stream = self::createStub(StreamInterface::class);
        $stream->method('__toString')->willReturn($body);
        $response->method('getBody')->willReturn($stream);

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
