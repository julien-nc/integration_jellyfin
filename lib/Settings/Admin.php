<?php
namespace OCA\Jellyfin\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

use OCA\Jellyfin\AppInfo\Application;

class Admin implements ISettings {

	private IConfig $config;
	private IInitialState $initialStateService;
	private ?string $userId;

	public function __construct(IConfig       $config,
								IInitialState $initialStateService,
								?string       $userId) {
		$this->config = $config;
		$this->initialStateService = $initialStateService;
		$this->userId = $userId;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$token = $this->config->getAppValue(Application::APP_ID, 'token');
		$jfUserId = $this->config->getAppValue(Application::APP_ID, 'user_id');
		$jfUserName = $this->config->getAppValue(Application::APP_ID, 'user_name');
		$jfServerId = $this->config->getAppValue(Application::APP_ID, 'server_id');
		$jfServerUrl = $this->config->getAppValue(Application::APP_ID, 'server_url');

		$userConfig = [
			'token' => $token,
			'user_id' => $jfUserId,
			'user_name' => $jfUserName,
			'server_id' => $jfServerId,
			'server_url' => $jfServerUrl,
		];
		$this->initialStateService->provideInitialState('admin-config', $userConfig);
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
