<?php
/**
 * Nextcloud - Jellyfin
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Jellyfin\AppInfo;

//use OCA\Jellyfin\Listener\JellyfinReferenceListener;
//use OCA\Jellyfin\Reference\JellyfinReferenceProvider;
use Closure;
//use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCA\Jellyfin\Reference\JellyfinReferenceProvider;
use OCP\IConfig;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;

use OCA\Jellyfin\Search\JellyfinSearchItemsProvider;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_jellyfin';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$this->container = $container;
		$this->config = $container->query(IConfig::class);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(JellyfinSearchItemsProvider::class);

		$context->registerReferenceProvider(JellyfinReferenceProvider::class);
//		$context->registerEventListener(RenderReferenceEvent::class, JellyfinReferenceListener::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerNavigation']));
	}

	public function registerNavigation(IUserSession $userSession): void {
		$user = $userSession->getUser();
		if ($user !== null) {
			$userId = $user->getUID();
			$container = $this->getContainer();

			if ($this->config->getUserValue($userId, self::APP_ID, 'navigation_enabled', '0') === '1') {
				$jellyfinUrl = $this->config->getAppValue(self::APP_ID, 'server_url');
				if ($jellyfinUrl === '') {
					return;
				}
				$serverName = $this->config->getAppValue(self::APP_ID, 'server_name');
				$l10n = $container->get(IL10N::class);
				$navName = $serverName ?: $l10n->t('Jellyfin');
				$container->get(INavigationManager::class)->add(function () use ($container, $jellyfinUrl, $navName) {
					$urlGenerator = $container->get(IURLGenerator::class);
					return [
						'id' => self::APP_ID,
						'order' => 10,
						'href' => $jellyfinUrl,
						'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
						'name' => $navName,
					];
				});
			}
		}
	}
}

