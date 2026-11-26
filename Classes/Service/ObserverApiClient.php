<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

class ObserverApiClient
{
    // TYPO3's default HTTP timeout is 0 (wait forever), which would hang the scheduler
    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    /**
     * @param array<string, mixed> $report
     */
    public function pushReport(array $report): void
    {
        [$apiUrl, $token] = $this->resolveSettings();

        $response = $this->requestFactory->request(
            rtrim($apiUrl, '/') . '/api/v1/sites/report',
            'POST',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    // without it the Observer answers a validation error with an HTML page
                    'Accept' => 'application/json',
                ],
                'body' => json_encode($report, JSON_THROW_ON_ERROR),
                'timeout' => self::TIMEOUT_SECONDS,
                // Guzzle would cut the Observer's error message short, so the status is checked here
                'http_errors' => false,
            ]
        );

        if ($response->getStatusCode() !== 202) {
            throw new \RuntimeException(sprintf('CMS Observer rejected the report (HTTP %d): %s', $response->getStatusCode(), $this->rejectionMessage($response)), 1757337602);
        }
    }

    /**
     * @return array{string, string} API URL and token
     */
    private function resolveSettings(): array
    {
        try {
            $config = (array)$this->extensionConfiguration->get('cms_observer_client');
        } catch (ExtensionConfigurationExtensionNotConfiguredException) {
            $config = [];
        }
        // Environment variables win, so one committed settings.php can serve staging and production
        $apiUrl = $this->setting('CMS_OBSERVER_API_URL', $config['apiUrl'] ?? '');
        $token = $this->setting('CMS_OBSERVER_TOKEN', $config['token'] ?? '');

        if ($apiUrl === '' || $token === '') {
            throw new \RuntimeException('CMS Observer client is not configured: set apiUrl and token in the extension settings, or the CMS_OBSERVER_API_URL and CMS_OBSERVER_TOKEN environment variables.', 1757337600);
        }
        // The token and the version inventory must never travel in cleartext
        if (strtolower((string)parse_url($apiUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new \RuntimeException(sprintf('CMS Observer API URL must use https, got "%s".', $apiUrl), 1757337601);
        }

        return [$apiUrl, $token];
    }

    private function rejectionMessage(ResponseInterface $response): string
    {
        $body = (array)json_decode((string)$response->getBody(), true);
        // "message" for a domain mismatch (409), "detail" names the invalid field of a validation error (422)
        $message = $body['message'] ?? $body['detail'] ?? null;

        return is_string($message) ? $message : $response->getReasonPhrase();
    }

    private function setting(string $envName, mixed $configValue): string
    {
        $value = getenv($envName);

        return trim(is_string($value) && trim($value) !== '' ? $value : (string)$configValue);
    }
}
