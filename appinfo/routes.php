<?php
/**
 * Nextcloud - Jellyfin
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2023
 */

return [
	'routes' => [
		['name' => 'config#setConfig', 'url' => '/config', 'verb' => 'PUT'],
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'jellyfinAPI#getMediaImage', 'url' => '/items/{itemId}/images/primary', 'verb' => 'GET'],
		['name' => 'jellyfinAPI#internalMediaLink', 'url' => '/i/{itemId}', 'verb' => 'GET'],
	],
];
