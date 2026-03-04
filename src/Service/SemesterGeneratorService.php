<?php

namespace Drupal\as_courses\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for generating semester codes and labels.
 *
 * Generates semester options dynamically based on configured year ranges,
 * eliminating the need for hardcoded semester lists that require annual updates.
 */
class SemesterGeneratorService {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Semester codes in order.
   *
   * @var array
   */
  const SEMESTER_CODES = ['FA', 'WI', 'SP', 'SU'];

  /**
   * Semester labels.
   *
   * @var array
   */
  const SEMESTER_LABELS = [
    'FA' => 'Fall',
    'WI' => 'Winter',
    'SP' => 'Spring',
    'SU' => 'Summer',
  ];

  /**
   * Constructs a SemesterGeneratorService object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    TimeInterface $time,
    ConfigFactoryInterface $config_factory
  ) {
    $this->time = $time;
    $this->configFactory = $config_factory;
  }

  /**
   * Gets semester options for form elements.
   *
   * Generates a list of semester codes and labels based on the configured
   * year range offsets.
   *
   * @return array
   *   Associative array of semester codes and labels
   *   (e.g., ['FA24' => 'Fall 2024', 'WI25' => 'Winter 2025']).
   */
  public function getSemesterOptions() {
    $config = $this->configFactory->get('as_courses.settings');
    $start_offset = $config->get('semester_start_year_offset') ?? -1;
    $end_offset = $config->get('semester_end_year_offset') ?? 5;

    $current_year = date('Y', $this->time->getRequestTime());
    $start_year = $current_year + $start_offset;
    $end_year = $current_year + $end_offset;

    return $this->getSemestersBetween($start_year, $end_year);
  }

  /**
   * Gets semesters between specified years.
   *
   * Generates semesters in chronological order following academic years:
   * Fall YYYY, Winter YYYY+1, Spring YYYY+1, Summer YYYY+1, Fall YYYY+1...
   *
   * @param int $start_year
   *   The starting year (4-digit).
   * @param int $end_year
   *   The ending year (4-digit).
   *
   * @return array
   *   Associative array of semester codes and labels in chronological order.
   */
  public function getSemestersBetween($start_year, $end_year) {
    $semesters = [];

    for ($year = $start_year; $year <= $end_year; $year++) {
      $year_short = substr((string) $year, -2);
      $next_year_short = substr((string) ($year + 1), -2);

      // Fall starts the academic year (uses current year).
      $semesters['FA' . $year_short] = 'Fall ' . $year;

      // Winter, Spring, Summer use the next year's code and label.
      // Only add these if the next year is within our range.
      if ($year + 1 <= $end_year) {
        $semesters['WI' . $next_year_short] = 'Winter ' . ($year + 1);
        $semesters['SP' . $next_year_short] = 'Spring ' . ($year + 1);
        $semesters['SU' . $next_year_short] = 'Summer ' . ($year + 1);
      }
    }

    return $semesters;
  }

  /**
   * Gets the current semester based on today's date.
   *
   * Uses simple date logic:
   * - Spring (SP): January 1 - May 31
   * - Summer (SU): June 1 - August 15
   * - Fall (FA): August 16 - December 31
   * - Winter (WI): December - January (intersession)
   *
   * @return string
   *   The current semester code (e.g., 'SP26').
   */
  public function getCurrentSemester() {
    $current_time = $this->time->getRequestTime();
    $month = (int) date('n', $current_time);
    $day = (int) date('j', $current_time);
    $year = (int) date('Y', $current_time);
    $year_short = substr((string) $year, -2);

    // Determine semester based on month and day.
    if ($month >= 1 && $month <= 5) {
      // Spring semester (Jan-May).
      return 'SP' . $year_short;
    }
    elseif ($month === 6 || $month === 7 || ($month === 8 && $day <= 15)) {
      // Summer semester (Jun - Aug 15).
      return 'SU' . $year_short;
    }
    elseif (($month === 8 && $day > 15) || $month === 9 || $month === 10 || $month === 11) {
      // Fall semester (Aug 16 - Nov).
      return 'FA' . $year_short;
    }
    else {
      // December - Winter intersession.
      // Winter is coded with next year's last 2 digits.
      $next_year_short = substr((string) ($year + 1), -2);
      return 'WI' . $next_year_short;
    }
  }

  /**
   * Formats a semester code into a human-readable name.
   *
   * @param string $semester_code
   *   The semester code (e.g., 'SP26', 'FA25').
   *
   * @return string
   *   The formatted semester name (e.g., 'Spring 2026', 'Fall 2025').
   */
  public function formatSemesterName($semester_code) {
    // Extract the semester type and year from the code.
    $type = substr($semester_code, 0, 2);
    $year_short = substr($semester_code, 2, 2);

    // Convert 2-digit year to 4-digit year.
    // Assume years 00-49 are 2000-2049, and 50-99 are 1950-1999.
    $year_int = (int) $year_short;
    $year_full = ($year_int < 50) ? 2000 + $year_int : 1900 + $year_int;

    // For Winter semester, the label year is the same as the code year.
    $label = self::SEMESTER_LABELS[$type] ?? $type;

    return $label . ' ' . $year_full;
  }

}
