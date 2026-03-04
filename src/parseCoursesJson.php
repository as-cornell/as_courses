<?php

namespace Drupal\as_courses;

use Drupal\as_courses\Service\CoursesApiService;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Twig extension for parsing courses JSON data.
 */
class parseCoursesJson extends \Twig\Extension\AbstractExtension implements ContainerInjectionInterface {

  /**
   * The courses API service.
   *
   * @var \Drupal\as_courses\Service\CoursesApiService
   */
  protected $coursesApi;

  /**
   * Constructs a parseCoursesJson object.
   *
   * @param \Drupal\as_courses\Service\CoursesApiService $courses_api
   *   The courses API service.
   */
  public function __construct(CoursesApiService $courses_api) {
    $this->coursesApi = $courses_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('as_courses.api')
    );
  }

  /**
   * {@inheritdoc}
   * Let Drupal know the name of custom extension
   */
  public function getName()
  {
    return 'as_courses.parse.json';
  }


  /**
   * {@inheritdoc}
   * Return custom twig function to Drupal
   */
  public function getFunctions()
  {
    return [
      new \Twig\TwigFunction('parse_courses_json', [$this, 'parse_courses_json']),
    ];
  }

  /**
   * Parses courses JSON data into array for theming
   *
   *
   * @return array $course_record
   *   data in array for theming
   */
  public function parse_courses_json($semester,$subjects,$courses_shown,$list_order)
  {
    $course_record = [];

    //convert from real number to 0 base
    if (!empty($courses_shown)) {
    $courses_shown = $courses_shown - 1;
    $course_count = 0;
    }

    $courses_json = $this->coursesApi->getCoursesBySubject($semester, $subjects);
    //multiple random courses with shuffle()
    //https://www.w3schools.com/php/func_array_shuffle.asp
    if ($list_order == 'random'){
      shuffle($courses_json);
      }
    if (!empty($courses_json)) {
      foreach ($courses_json as $course_data) {
        if (!empty($courses_shown) &&  $course_count <= $courses_shown) {
          // get a certain number of courses
          $course_record[] = array('subject' => $course_data['subject'], 'number' => $course_data['catalogNbr'], 'title' => $course_data['titleLong'], 'description' => $course_data['description'], 'offered' => $course_data['catalogWhenOffered'], 'acadGroup' => $course_data['acadGroup'], 'acadCareer' => $course_data['acadCareer'],'catalogDistr' => $course_data['catalogDistr']);
          $course_count++;
        }
        if (empty($courses_shown)) {
          // get all courses
          $course_record[] = array('subject' => $course_data['subject'], 'number' => $course_data['catalogNbr'], 'title' => $course_data['titleLong'], 'description' => $course_data['description'], 'offered' => $course_data['catalogWhenOffered'], 'acadGroup' => $course_data['acadGroup'], 'acadCareer' => $course_data['acadCareer'],'catalogDistr' => $course_data['catalogDistr']);
        }
      }
    }
    return $course_record;
  }
}
