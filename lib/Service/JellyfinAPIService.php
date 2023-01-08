<?php
/**
 * Nextcloud - Jellyfin
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Jellyfin\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Jellyfin\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use OCP\Http\Client\IClientService;
use Throwable;

class JellyfinAPIService {
	private LoggerInterface $logger;
	private IL10N $l10n;
	private IConfig $config;
	private IURLGenerator $urlGenerator;
	private IClient $client;

	/**
	 * Service to make requests to Jellyfin REST API
	 */
	public function __construct (string $appName,
								LoggerInterface $logger,
								IL10N $l10n,
								IConfig $config,
								IURLGenerator $urlGenerator,
								IClientService $clientService) {
		$this->client = $clientService->newClient();
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param string $itemId
	 * @return array
	 */
	public function getItemInfo(string $itemId): array {
		// TODO
		$response = $this->request($itemId);
		if (!isset($response['error']) && isset($response['data'])) {
			return $response['data'];
		}
		return $response;
	}

	/**
	 * @param string $itemId
	 * @return string|null
	 */
	public function getDownloadLink(string $itemId): ?string {
		$token = $this->config->getAppValue(Application::APP_ID, 'token');
		$jfServerUrl = $this->config->getAppValue(Application::APP_ID, 'server_url');
		if ($token && $jfServerUrl) {
			return $jfServerUrl . '/items/' . $itemId . '/download?api_key=' . $token;
		}
		return null;
	}

	/**
	 * Search items
	 *
	 * @param string $userId
	 * @param string $query
	 * @param int $offset
	 * @param int $limit
	 * @return array request result
	 */
	public function searchItems(string $userId, string $query, int $offset = 0, int $limit = 5): array {
		$jfUserId = $this->config->getAppValue(Application::APP_ID, 'user_id');
		$params = [
			'searchTerm' => $query,
			'Recursive' => 'true',
			'Limit' => $limit,
			'startIndex' => $offset,
		];
		$result = $this->request($userId, 'users/' . $jfUserId . '/items', $params);
		if (!isset($result['error']) && isset($result['Items']) && is_array($result['Items'])) {
			return $result['Items'];
		}
		return $result;
	}

	/**
	 * @param string $userId
	 * @param string $itemId
	 * @param int $fillHeight
	 * @param int $fillWidth
	 * @param int $quality
	 * @return array
	 */
	public function getMediaImage(string $userId, string $itemId, int $fillHeight = 44, int $fillWidth = 44, int $quality = 96): array {
		$endpoint = 'items/' . $itemId . '/images/primary';
		$params = [
			'fillHeight' => $fillHeight,
			'fillWidth' => $fillWidth,
			'quality' => $quality,
		];
		return $this->request($userId, $endpoint, $params, 'GET', true);
	}

	/**
	 * @param string $serverUrl
	 * @param string $login
	 * @param string $password
	 * @return array
	 */
	public function login(string $serverUrl, string $login, string $password): array {
		try {
			$url = $serverUrl . '/Users/AuthenticateByName';
			$params = [
				'Username' => $login,
				'Pw' => $password,
			];
			$options = [
				'headers' => [
					'User-Agent' => 'Nextcloud Jellyfin integration',
					'Authorization' => 'MediaBrowser Client="NC", Device="NCserver", DeviceId="whatever", Version="26"',
					'Content-Type' => 'application/json',
				],
				'body' => json_encode($params),
			];

			$response = $this->client->post($url, $options);
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true) ?: [];
			}
		} catch (ClientException | ServerException $e) {
			$responseBody = $e->getResponse()->getBody();
			$parsedResponseBody = json_decode($responseBody, true);
			$this->logger->debug('Jellyfin login error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			return [
				'error' => $e->getMessage(),
				'body' => $parsedResponseBody,
			];
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Jellyfin login error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	public function logout(): array {
		return $this->request(null, 'sessions/logout', [], 'POST');
	}

	/**
	 * Make an HTTP request to the Jellyfin API
	 * @param string|null $userId
	 * @param string $endPoint The path to reach in api.github.com
	 * @param array $params Query parameters (key/val pairs)
	 * @param string $method HTTP query method
	 * @param bool $rawResponse
	 * @return array decoded request result or error
	 */
	public function request(?string $userId, string $endPoint, array $params = [], string $method = 'GET', bool $rawResponse = false): array {
		$jfServerUrl = $this->config->getAppValue(Application::APP_ID, 'server_url');
		$token = $this->config->getAppValue(Application::APP_ID, 'token');
		try {
			$url = $jfServerUrl . '/' . $endPoint;
			$options = [
				'headers' => [
					'User-Agent' => 'Nextcloud Jellyfin integration',
					'Authorization' => 'MediaBrowser Token="' . $token . '"',
					'Content-Type' => 'application/json',
				],
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['body'] = json_encode($params);
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} else if ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} else if ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} else if ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				if ($rawResponse) {
					return [
						'body' => $body,
						'headers' => $response->getHeaders(),
					];
				} else {
					return json_decode($body, true) ?: [];
				}
			}
		} catch (ClientException | ServerException $e) {
			$responseBody = $e->getResponse()->getBody();
			$parsedResponseBody = json_decode($responseBody, true);
			if ($e->getResponse()->getStatusCode() === 404) {
				// Only log inaccessible github links as debug
				$this->logger->debug('Jellyfin API error : ' . $e->getMessage(), ['response_body' => $parsedResponseBody, 'app' => Application::APP_ID]);
			} else {
				$this->logger->warning('Jellyfin API error : ' . $e->getMessage(), ['response_body' => $parsedResponseBody, 'app' => Application::APP_ID]);
			}
			return [
				'error' => $e->getMessage(),
				'body' => $parsedResponseBody,
			];
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Jellyfin API error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
