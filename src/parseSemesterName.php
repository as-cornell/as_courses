<?php

namespace Drupal\as_courses;

use Drupal\as_courses\Service\SemesterGeneratorService;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Twig extension for parsing semester names.
 */
class parseSemesterName extends \Twig\Extension\AbstractExtension implements ContainerInjectionInterface {

  /**
   * The semester generator service.
   *
   * @var \Drupal\as_courses\Service\SemesterGeneratorService
   */
  protected $semesterGenerator;

  /**
   * Constructs a parseSemesterName object.
   *
   * @param \Drupal\as_courses\Service\SemesterGeneratorService $semester_generator
   *   The semester generator service.
   */
  public function __construct(SemesterGeneratorService $semester_generator) {
    $this->semesterGenerator = $semester_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('as_courses.semester_generator')
    );
  }

  /**
   * {@inheritdoc}
   * Let Drupal know the name of custom extension
   */
  public function getName()
  {
    return 'as_courses.parse.semestername';
  }


  /**
   * {@inheritdoc}
   * Return custom twig function to Drupal
   */
  public function getFunctions()
  {
    return [
      new \Twig\TwigFunction('parse_semester_name', [$this, 'parse_semester_name']),
    ];
  }

  /**
   * Parses semster key value into readabel name
   *
   *
   * @return string $semester_name
   *   string for labels
   */
  public function parse_semester_name($semester)
  {

    if (!empty($semester)) {
      // Use the semester generator service to format the name.
      return $this->semesterGenerator->formatSemesterName($semester);
    }
    return $semester;
  }
}
