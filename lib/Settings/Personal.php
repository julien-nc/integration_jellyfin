<?php
namespace OCA\Jellyfin\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

use OCA\Jellyfin\AppInfo\Application;

class Personal implements ISettings {

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
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$searchItemsEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_items_enabled', '1') === '1';
		$navigationEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'navigation_enabled', '0') === '1';
		$linkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '1') === '1';

		$jfUserId = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_id');
		$jfUserName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_name');
		$jfServerId = $this->config->getUserValue($this->userId, Application::APP_ID, 'server_id');
		$jfServerUrl = $this->config->getUserValue($this->userId, Application::APP_ID, 'server_url');

		$userConfig = [
			'token' => $token,
			'user_id' => $jfUserId,
			'user_name' => $jfUserName,
			'server_id' => $jfServerId,
			'server_url' => $jfServerUrl,
			'search_items_enabled' => $searchItemsEnabled,
			'navigation_enabled' => $navigationEnabled ,
			'link_preview_enabled' => $linkPreviewEnabled,
		];
		$this->initialStateService->provideInitialState('user-config', $userConfig);
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
