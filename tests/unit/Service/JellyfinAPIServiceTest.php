<?php

namespace OCA\Jellyfin\Tests;


use OCA\Jellyfin\AppInfo\Application;
use OCA\Jellyfin\Service\JellyfinAPIService;

class JellyfinAPIServiceTest extends \PHPUnit\Framework\TestCase {

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('integration_jellyfin', $app::APP_ID);
	}
}
