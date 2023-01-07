<?php
/**
 * Nextcloud - Jellyfin
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Jellyfin\Controller;

use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

use OCA\Jellyfin\Service\JellyfinAPIService;
use OCP\IURLGenerator;

class JellyfinAPIController extends OCSController {

	private JellyfinAPIService $jellyfinAPIService;
	private IURLGenerator $urlGenerator;
	private ?string $userId;

	public function __construct(string          $appName,
								IRequest        $request,
								JellyfinAPIService $jellyfinAPIService,
								IURLGenerator   $urlGenerator,
								?string         $userId) {
		parent::__construct($appName, $request);
		$this->jellyfinAPIService = $jellyfinAPIService;
		$this->urlGenerator = $urlGenerator;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $itemId
	 * @param string $fallbackName
	 * @param int $fillHeight
	 * @param int $fillWidth
	 * @param int $quality
	 * @return DataDownloadResponse|RedirectResponse
	 */
	public function getMediaImage(string $itemId, string $fallbackName, int $fillHeight = 44, int $fillWidth = 44, int $quality = 96) {
		$result = $this->jellyfinAPIService->getMediaImage($this->userId, $itemId, $fillHeight, $fillWidth, $quality);
		if (isset($result['error'])) {
			$fallbackAvatarUrl = $this->urlGenerator->linkToRouteAbsolute('core.GuestAvatar.getAvatar', ['guestName' => $fallbackName, 'size' => 44]);
			return new RedirectResponse($fallbackAvatarUrl);
		} else {
			$response = new DataDownloadResponse(
				$result['body'],
				'',
				$result['headers']['Content-Type'][0] ?? 'image/jpeg'
			);
			$response->cacheFor(60 * 60 * 24);
			return $response;
		}
	}
}
