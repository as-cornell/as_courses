<?php

namespace Drupal\Tests\as_courses\Unit;

use Drupal\as_courses\Service\CoursesApiService;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit tests for CoursesApiService.
 *
 * @group as_courses
 * @coversDefaultClass \Drupal\as_courses\Service\CoursesApiService
 */
class CoursesApiServiceTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The mocked HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $httpClient;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cache;

  /**
   * The mocked logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * The CoursesApiService instance.
   *
   * @var \Drupal\as_courses\Service\CoursesApiService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->prophesize(ClientInterface::class);
    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);

    // Mock config.
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('api_timeout')->willReturn(10);
    $config->get('cache_duration')->willReturn(3600);
    $this->configFactory->get('as_courses.settings')->willReturn($config->reveal());

    $this->service = new CoursesApiService(
      $this->httpClient->reveal(),
      $this->cache->reveal(),
      $this->logger->reveal(),
      $this->configFactory->reveal()
    );
  }

  /**
   * Tests getCoursesBySubject with cached data.
   *
   * @covers ::getCoursesBySubject
   */
  public function testGetCoursesBySubjectWithCache() {
    $semester = 'SP26';
    $subjects = 'PSYCH';
    $cid = "as_courses:{$semester}:{$subjects}";
    $cached_data = [['subject' => 'PSYCH', 'catalogNbr' => '1101']];

    // Mock cache hit.
    $cache_item = (object) ['data' => $cached_data];
    $this->cache->get($cid)->willReturn($cache_item);

    $result = $this->service->getCoursesBySubject($semester, $subjects);

    $this->assertEquals($cached_data, $result);
  }

  /**
   * Tests getCoursesBySubject with API call.
   *
   * @covers ::getCoursesBySubject
   * @covers ::fetchFromApi
   */
  public function testGetCoursesBySubjectWithApiCall() {
    $semester = 'SP26';
    $subjects = 'PSYCH';
    $cid = "as_courses:{$semester}:{$subjects}";

    // Mock cache miss.
    $this->cache->get($cid)->willReturn(FALSE);

    // Mock API response.
    $api_data = [
      'data' => [
        'classes' => [
          ['subject' => 'PSYCH', 'catalogNbr' => '1101'],
        ],
      ],
    ];
    $response = new Response(200, [], json_encode($api_data));
    $this->httpClient->request('GET', Argument::containingString('roster=SP26'), Argument::any())
      ->willReturn($response);

    // Expect cache set.
    $this->cache->set($cid, $api_data['data']['classes'], Argument::type('int'))
      ->shouldBeCalled();

    $result = $this->service->getCoursesBySubject($semester, $subjects);

    $this->assertEquals($api_data['data']['classes'], $result);
  }

  /**
   * Tests getCoursesByInstructor.
   *
   * @covers ::getCoursesByInstructor
   */
  public function testGetCoursesByInstructor() {
    $semester = 'FA25';
    $netid = 'abc123';
    $cid = "as_courses:{$semester}:{$netid}";

    // Mock cache miss.
    $this->cache->get($cid)->willReturn(FALSE);

    // Mock API response.
    $api_data = [
      'data' => [
        'classes' => [
          ['subject' => 'PSYCH', 'catalogNbr' => '2200'],
        ],
      ],
    ];
    $response = new Response(200, [], json_encode($api_data));
    $this->httpClient->request('GET', Argument::containingString('instructor=' . $netid), Argument::any())
      ->willReturn($response);

    // Expect cache set.
    $this->cache->set($cid, $api_data['data']['classes'], Argument::type('int'))
      ->shouldBeCalled();

    $result = $this->service->getCoursesByInstructor($semester, $netid);

    $this->assertEquals($api_data['data']['classes'], $result);
  }

  /**
   * Tests API error handling.
   *
   * @covers ::fetchFromApi
   */
  public function testApiErrorHandling() {
    $semester = 'SP26';
    $subjects = 'INVALID';
    $cid = "as_courses:{$semester}:{$subjects}";

    // Mock cache miss.
    $this->cache->get($cid)->willReturn(FALSE);

    // Mock API error.
    $this->httpClient->request('GET', Argument::any(), Argument::any())
      ->willThrow(new RequestException('API Error', $this->prophesize(\Psr\Http\Message\RequestInterface::class)->reveal()));

    // Expect error logging.
    $this->logger->error(Argument::containingString('Failed to fetch courses'), Argument::any())
      ->shouldBeCalled();

    $result = $this->service->getCoursesBySubject($semester, $subjects);

    $this->assertNull($result);
  }

}
