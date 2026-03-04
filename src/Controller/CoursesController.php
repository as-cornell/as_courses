<?php

namespace Drupal\as_courses\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the courses page.
 */
class CoursesController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a CoursesController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Displays the courses page.
   *
   * @param string $semester
   *   The semester code (optional).
   *
   * @return array
   *   A render array for the courses page.
   */
  public function content($semester) {

    $defaultsemester = $this->configFactory->get("as_courses.defaults")->get("defaultsemester");
    if (!empty($defaultsemester)) {
      if (empty($semester)) {
        // Set empty semester to current default semester.
        $semester = $defaultsemester;
      }
    }
    $course_prefixes = $this->configFactory->get("as_courses.defaults")->get("course_prefixes");


    return [
      '#theme' => 'courses',
      '#semester' => $semester,
      '#course_prefixes' => $course_prefixes
    ];

  }


}
