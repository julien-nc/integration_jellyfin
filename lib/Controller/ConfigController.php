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

use OCA\Jellyfin\Service\JellyfinAPIService;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCA\Jellyfin\AppInfo\Application;

class ConfigController extends Controller {

	private IConfig $config;
	private ?string $userId;
	private JellyfinAPIService $jellyfinAPIService;

	public function __construct(string   $appName,
								IRequest $request,
								IConfig  $config,
								JellyfinAPIService $jellyfinAPIService,
								?string  $userId) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->userId = $userId;
		$this->jellyfinAPIService = $jellyfinAPIService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param array $values
	 * @return DataResponse
	 */
	public function setConfig(array $values): DataResponse {
		if (isset($values['login'], $values['password'], $values['server_url'])) {
			return $this->loginWithCredentials($values['server_url'], $values['login'], $values['password']);
		}

		$result = [];
		if (isset($values['user_name'])) {
//			$this->jellyfinReferenceProvider->invalidateUserCache($this->userId);
			if ($values['user_name'] === '') {
				$logoutResponse = $this->jellyfinAPIService->logout($this->userId);
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'user_id');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'user_name');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'server_id');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'server_name');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'token');
				return new DataResponse($result);
			}
		}

		foreach ($values as $key => $value) {
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}

		return new DataResponse($result);
	}

	private function loginWithCredentials(string $serverUrl, string $login, string $password): DataResponse {
		$result = $this->jellyfinAPIService->login($serverUrl, $login, $password);
		if (isset(
			$result['AccessToken'], $result['ServerId'], $result['User'],
			$result['User']['Name'], $result['User']['Id']
		)) {
			$this->config->setUserValue($this->userId, Application::APP_ID, 'token', $result['AccessToken']);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'server_id', $result['ServerId']);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_id', $result['User']['Id']);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', $result['User']['Name']);
			$serverInfo = $this->jellyfinAPIService->request($this->userId, 'system/info');
			if (isset($serverInfo['ServerName'])) {
				$this->config->setUserValue($this->userId, Application::APP_ID, 'server_name', $serverInfo['ServerName']);
			}
			return new DataResponse([
				'user_id' => $result['User']['Id'],
				'user_name' => $result['User']['Name'],
				'server_id' => $result['ServerId'],
				'server_name' => $serverInfo['ServerName'],
				'token' => 'yes',
			]);
		}
		return new DataResponse([
			'user_id' => '',
			'user_name' => '',
		]);
	}
}
