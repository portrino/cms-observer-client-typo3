<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

class ObserverApiClient
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
    }

    /**
     * @param array{coreVersion: string, phpVersion: string, dbVersion: string, extensions: array} $report
     */
    public function pushReport(array $report): bool
    {
        $config = (array) $this->extensionConfiguration->get('cms_observer_client');
        $apiUrl = trim((string) ($config['apiUrl'] ?? ''));
        $token = trim((string) ($config['token'] ?? ''));

        if ('' === $apiUrl || '' === $token) {
            throw new \RuntimeException('CMS Observer client is not configured: set apiUrl and token in the extension settings.', 1757337600);
        }

        $response = $this->requestFactory->request(
            rtrim($apiUrl, '/').'/api/v1/sites/report',
            'POST',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($report, JSON_THROW_ON_ERROR),
            ]
        );

        return 202 === $response->getStatusCode();
    }
}
