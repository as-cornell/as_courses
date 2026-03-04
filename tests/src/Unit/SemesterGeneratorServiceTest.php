<?php

namespace Drupal\Tests\as_courses\Unit;

use Drupal\as_courses\Service\SemesterGeneratorService;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit tests for SemesterGeneratorService.
 *
 * @group as_courses
 * @coversDefaultClass \Drupal\as_courses\Service\SemesterGeneratorService
 */
class SemesterGeneratorServiceTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The mocked time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $time;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * The SemesterGeneratorService instance.
   *
   * @var \Drupal\as_courses\Service\SemesterGeneratorService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->time = $this->prophesize(TimeInterface::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);

    // Mock config with default offsets.
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('semester_start_year_offset')->willReturn(-1);
    $config->get('semester_end_year_offset')->willReturn(5);
    $this->configFactory->get('as_courses.settings')->willReturn($config->reveal());

    $this->service = new SemesterGeneratorService(
      $this->time->reveal(),
      $this->configFactory->reveal()
    );
  }

  /**
   * Tests getSemestersBetween generates correct format.
   *
   * @covers ::getSemestersBetween
   */
  public function testGetSemestersBetween() {
    $semesters = $this->service->getSemestersBetween(2025, 2026);

    // Should have 8 semesters in chronological order.
    // 2025: FA25, WI26, SP26, SU26
    // 2026: FA26, WI27, SP27, SU27
    $this->assertCount(8, $semesters);

    // Check specific semester codes and labels in chronological order.
    $this->assertEquals('Fall 2025', $semesters['FA25']);
    $this->assertEquals('Winter 2026', $semesters['WI26']);
    $this->assertEquals('Spring 2026', $semesters['SP26']);
    $this->assertEquals('Summer 2026', $semesters['SU26']);
    $this->assertEquals('Fall 2026', $semesters['FA26']);
    $this->assertEquals('Winter 2027', $semesters['WI27']);
    $this->assertEquals('Spring 2027', $semesters['SP27']);
    $this->assertEquals('Summer 2027', $semesters['SU27']);

    // Verify chronological order.
    $keys = array_keys($semesters);
    $expected_order = ['FA25', 'WI26', 'SP26', 'SU26', 'FA26', 'WI27', 'SP27', 'SU27'];
    $this->assertEquals($expected_order, $keys);
  }

  /**
   * Tests getSemesterOptions uses config offsets.
   *
   * @covers ::getSemesterOptions
   */
  public function testGetSemesterOptions() {
    // Mock current year as 2026.
    $this->time->getRequestTime()->willReturn(strtotime('2026-03-04'));

    $semesters = $this->service->getSemesterOptions();

    // With offset -1 to +5, generates chronologically:
    // Years: 2025-2031 (7 years)
    // FA25, WI26, SP26, SU26, FA26... through FA31
    // Note: Last year (2031) only includes FA31 since WI32+ would be outside range.
    $this->assertCount(25, $semesters);

    // Check first and last semesters.
    $this->assertArrayHasKey('FA25', $semesters);
    $this->assertArrayHasKey('FA31', $semesters);

    // Verify chronological order.
    $keys = array_keys($semesters);
    $this->assertEquals('FA25', $keys[0]);
    $this->assertEquals('FA31', $keys[count($keys) - 1]);
  }

  /**
   * Tests getCurrentSemester for different dates.
   *
   * @covers ::getCurrentSemester
   * @dataProvider getCurrentSemesterProvider
   */
  public function testGetCurrentSemester($date, $expected_semester) {
    $this->time->getRequestTime()->willReturn(strtotime($date));

    $result = $this->service->getCurrentSemester();

    $this->assertEquals($expected_semester, $result);
  }

  /**
   * Data provider for testGetCurrentSemester.
   *
   * @return array
   *   Test cases with date and expected semester.
   */
  public function getCurrentSemesterProvider() {
    return [
      // Spring semester (Jan-May).
      ['2026-01-15', 'SP26'],
      ['2026-03-04', 'SP26'],
      ['2026-05-31', 'SP26'],
      // Summer semester (Jun - Aug 15).
      ['2026-06-01', 'SU26'],
      ['2026-07-15', 'SU26'],
      ['2026-08-15', 'SU26'],
      // Fall semester (Aug 16 - Nov).
      ['2026-08-16', 'FA26'],
      ['2026-09-01', 'FA26'],
      ['2026-11-30', 'FA26'],
      // Winter intersession (December).
      ['2026-12-15', 'WI27'],
      ['2025-12-31', 'WI26'],
    ];
  }

  /**
   * Tests formatSemesterName.
   *
   * @covers ::formatSemesterName
   * @dataProvider formatSemesterNameProvider
   */
  public function testFormatSemesterName($code, $expected_name) {
    $result = $this->service->formatSemesterName($code);

    $this->assertEquals($expected_name, $result);
  }

  /**
   * Data provider for testFormatSemesterName.
   *
   * @return array
   *   Test cases with semester codes and expected names.
   */
  public function formatSemesterNameProvider() {
    return [
      ['SP26', 'Spring 2026'],
      ['SU25', 'Summer 2025'],
      ['FA24', 'Fall 2024'],
      ['WI25', 'Winter 2025'],
      ['FA99', 'Fall 1999'],
      ['SP00', 'Spring 2000'],
    ];
  }

}
