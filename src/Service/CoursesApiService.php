<?php

namespace Drupal\as_courses\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for interacting with the Cornell Classes API.
 *
 * Provides methods to fetch course data by subject or instructor from
 * the Cornell Classes API (classes.cornell.edu).
 */
class CoursesApiService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The base URL for the Cornell Classes API.
   *
   * @var string
   */
  const API_BASE_URL = 'https://classes.cornell.edu/api/2.0/search/classes.json';

  /**
   * Constructs a CoursesApiService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    ClientInterface $http_client,
    CacheBackendInterface $cache,
    LoggerChannelInterface $logger,
    ConfigFactoryInterface $config_factory
  ) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * Fetches courses by subject for a given semester.
   *
   * @param string $semester
   *   The semester code (e.g., 'SP26', 'FA25').
   * @param string $subjects
   *   Comma-separated list of subject codes (e.g., 'PSYCH,ECON').
   *
   * @return array|null
   *   Array of course data, or NULL if no courses found or error occurred.
   */
  public function getCoursesBySubject($semester, $subjects) {
    $cid = "as_courses:{$semester}:{$subjects}";

    // Check cache first.
    if ($cache = $this->cache->get($cid)) {
      if (!empty($cache->data)) {
        return $cache->data;
      }
    }

    // Build API URL.
    $url = self::API_BASE_URL . "?roster={$semester}&subject={$subjects}";

    // Fetch from API.
    $courses_json = $this->fetchFromApi($url, $cid);

    return $courses_json;
  }

  /**
   * Fetches courses by instructor netid for a given semester.
   *
   * @param string $semester
   *   The semester code (e.g., 'SP26', 'FA25').
   * @param string $netid
   *   The instructor's Cornell NetID.
   *
   * @return array|null
   *   Array of course data, or NULL if no courses found or error occurred.
   */
  public function getCoursesByInstructor($semester, $netid) {
    $cid = "as_courses:{$semester}:{$netid}";

    // Check cache first.
    if ($cache = $this->cache->get($cid)) {
      if (!empty($cache->data)) {
        return $cache->data;
      }
    }

    // Build API URL.
    $url = self::API_BASE_URL . "?roster={$semester}&instructor={$netid}";

    // Fetch from API.
    $courses_json = $this->fetchFromApi($url, $cid);

    return $courses_json;
  }

  /**
   * Fetches data from the Cornell Classes API.
   *
   * @param string $url
   *   The API URL to fetch from.
   * @param string $cid
   *   The cache ID to use for storing the result.
   *
   * @return array|null
   *   Array of course data, or NULL if error occurred.
   */
  protected function fetchFromApi($url, $cid) {
    $config = $this->configFactory->get('as_courses.settings');
    $timeout = $config->get('api_timeout') ?? 10;
    $cache_duration = $config->get('cache_duration') ?? 3600;

    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => $timeout,
        'connect_timeout' => $timeout,
      ]);

      $data = $response->getBody()->getContents();

      if (!empty($data)) {
        $json = json_decode($data, TRUE);

        if (is_array($json) && !empty($json['data']['classes'])) {
          $courses_json = $json['data']['classes'];

          // Set cache.
          $this->cache->set($cid, $courses_json, time() + $cache_duration);

          return $courses_json;
        }
        else {
          $this->logger->warning('No courses found in API response for URL: @url', [
            '@url' => $url,
          ]);
          return NULL;
        }
      }
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to fetch courses from Cornell API: @message. URL: @url', [
        '@message' => $e->getMessage(),
        '@url' => $url,
      ]);
      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Unexpected error fetching courses: @message. URL: @url', [
        '@message' => $e->getMessage(),
        '@url' => $url,
      ]);
      return NULL;
    }

    return NULL;
  }

}
