<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

class ExtensionInventoryService
{
    public function __construct(
        private readonly ListUtility $listUtility,
        private readonly ConnectionPool $connectionPool,
        private readonly RemoteRegistry $remoteRegistry,
    ) {
    }

    /**
     * @return array<int, array{name: string, title: string, version: string, active: bool, insecure: bool|null}>
     */
    public function getInstalledExtensions(): array
    {
        $this->refreshTerExtensionList();
        $reviewStates = $this->getReviewStates();

        $extensions = [];

        foreach ($this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation() as $key => $extension) {
            // Skip TYPO3 system extensions, only report third-party extensions.
            if (($extension['type'] ?? '') !== 'Local') {
                continue;
            }

            $version = (string) ($extension['version'] ?? '0.0.0');

            $extensions[] = [
                'name' => $key,
                'title' => (string) ($extension['title'] ?? $key),
                'version' => $version,
                'active' => (bool) ($extension['installed'] ?? false),
                'insecure' => $reviewStates[$key . ':' . $version] ?? null,
            ];
        }

        return $extensions;
    }

    /**
     * Refreshes TYPO3's own local TER cache (tx_extensionmanager_domain_model_extension),
     * the same step TYPO3's Extension Manager backend module runs. Without this, the
     * review_state lookup below would only reflect whatever was last cached.
     */
    private function refreshTerExtensionList(): void
    {
        foreach ($this->remoteRegistry->getListableRemotes() as $remote) {
            try {
                $remote->getAvailablePackages();
            } catch (\Throwable) {
                // TER might be unreachable from this host; fall back to whatever is cached.
            }
        }
    }

    /**
     * TER's own reviewers flag known-insecure extension releases via review_state.
     * This mirrors how TYPO3's Extension Manager and georgringer/t3monitoring both
     * determine "insecure" for extensions - there is no separate public CVE feed for it.
     *
     * @return array<string, bool> keyed by "extension_key:version"
     */
    private function getReviewStates(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_extensionmanager_domain_model_extension');
        $rows = $queryBuilder
            ->select('extension_key', 'version', 'review_state')
            ->from('tx_extensionmanager_domain_model_extension')
            ->executeQuery()
            ->fetchAllAssociative();

        $reviewStates = [];
        foreach ($rows as $row) {
            $reviewStates[$row['extension_key'] . ':' . $row['version']] = ((int) $row['review_state']) === -1;
        }

        return $reviewStates;
    }
}
