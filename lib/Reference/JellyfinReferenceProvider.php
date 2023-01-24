<?php
/**
 * @copyright Copyright (c) 2022 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Jellyfin\Reference;

use OC\Collaboration\Reference\LinkReferenceProvider;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OC\Collaboration\Reference\ReferenceManager;
use OCA\Jellyfin\AppInfo\Application;
use OCA\Jellyfin\Service\JellyfinAPIService;
use OCP\Collaboration\Reference\IReference;
use OCP\IConfig;
use OCP\IL10N;

use OCP\IURLGenerator;

class JellyfinReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	private const RICH_OBJECT_TYPE = Application::APP_ID . '_item';

	private JellyfinAPIService $jellyfinAPIService;
	private ?string $userId;
	private IConfig $config;
	private ReferenceManager $referenceManager;
	private IL10N $l10n;
	private IURLGenerator $urlGenerator;
	private LinkReferenceProvider $linkReferenceProvider;

	public function __construct(JellyfinAPIService $jellyfinAPIService,
								IConfig $config,
								IL10N $l10n,
								IURLGenerator $urlGenerator,
								ReferenceManager $referenceManager,
								LinkReferenceProvider $linkReferenceProvider,
								?string $userId) {
		$this->jellyfinAPIService = $jellyfinAPIService;
		$this->userId = $userId;
		$this->config = $config;
		$this->referenceManager = $referenceManager;
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->linkReferenceProvider = $linkReferenceProvider;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string	{
		return 'jellyfin-items';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Jellyfin media items');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int	{
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedSearchProviderIds(): array {
		if ($this->userId !== null) {
			$ids = [];
			$searchItemsEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_items_enabled', '1') === '1';
			if ($searchItemsEnabled) {
				$ids[] = 'jellyfin-search-items';
			}
			return $ids;
		}
		return ['jellyfin-search-items'];
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		$userLinkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled || !$userLinkPreviewEnabled) {
			return false;
		}

		$start = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID);
		$startIndex = $this->urlGenerator->getAbsoluteURL('/index.php/apps/' . Application::APP_ID);

		// link example: https://nextcloud.local/index.php/apps/integration_jellyfin/i/174af3d43325e1e8fa9f394e6096f3ba
		$noIndexMatch = preg_match('/^' . preg_quote($start, '/') . '\/i\/[0-9a-z]+$/i', $referenceText) === 1;
		$indexMatch = preg_match('/^' . preg_quote($startIndex, '/') . '\/i\/[0-9a-z]+$/i', $referenceText) === 1;

		return $noIndexMatch || $indexMatch;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$itemId = $this->getItemId($referenceText);
			if ($itemId !== null) {
				$itemInfo = $this->jellyfinAPIService->getItemInfo($itemId);
				if (!isset($itemInfo['error'])) {
					$reference = new Reference($referenceText);
					$reference->setTitle($this->jellyfinAPIService->getItemMainText($itemInfo));
					$description = $this->jellyfinAPIService->getItemSubText($itemInfo);
					if (isset($itemInfo['Overview'])) {
						$description .= ' - ' . $itemInfo['Overview'];
					}
					$reference->setDescription($description);
					$reference->setImageUrl($this->jellyfinAPIService->getItemThumbnailUrl($itemInfo, 300, 300));
					$reference->setRichObject(
						self::RICH_OBJECT_TYPE,
						$itemInfo,
					);
					return $reference;
				}
			}
			// fallback to opengraph
			return $this->linkReferenceProvider->resolveReference($referenceText);
		}

		return null;
	}

	/**
	 * @param string $url
	 * @return string|null
	 */
	private function getItemId(string $url): ?string {
		preg_match('/\/i\/([0-9a-z]+)$/i', $url, $matches);
		if (count($matches) > 1) {
			return $matches[1];
		}
		return null;
	}

	/**
	 * We use the userId here because when connecting/disconnecting from the GitHub account,
	 * we want to invalidate all the user cache and this is only possible with the cache prefix
	 * @inheritDoc
	 */
	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	/**
	 * We don't use the userId here but rather a reference unique id
	 * @inheritDoc
	 */
	public function getCacheKey(string $referenceId): ?string {
		$gifId = $this->getItemId($referenceId);
		if ($gifId !== null) {
			return $gifId;
		}

		return $referenceId;
	}

	/**
	 * @param string $userId
	 * @return void
	 */
	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}
}
