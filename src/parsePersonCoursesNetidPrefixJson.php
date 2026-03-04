<?php

namespace Drupal\as_courses;

use Drupal\as_courses\Service\CoursesApiService;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Twig extension for parsing person courses by NetID and subject prefix.
 */
class parsePersonCoursesNetidPrefixJson extends \Twig\Extension\AbstractExtension implements ContainerInjectionInterface {

  /**
   * The courses API service.
   *
   * @var \Drupal\as_courses\Service\CoursesApiService
   */
  protected $coursesApi;

  /**
   * Constructs a parsePersonCoursesNetidPrefixJson object.
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
    return 'as_courses.parse.netid.prefix.json';
  }


  /**
   * {@inheritdoc}
   * Return custom twig function to Drupal
   */
  public function getFunctions()
  {
    return [
      new \Twig\TwigFunction('parse_person_courses_netid_prefix_json', [$this, 'parse_person_courses_netid_prefix_json']),
    ];
  }

  /**
   * Parses a single person's courses JSON data into array for theming
   *
   *
   * @return array $person_course_record
   *   data in array for theming
   */
  public function parse_person_courses_netid_prefix_json($semester,$subjects,$netid)
  {
    $person_course_record = [];
    $filtersubject = '';
    $course_record = [];
    $showdebug = '';
    // would like to change this to use a similar approach as in web/modules/custom/as_people_ldap
    //if (PANTHEON_ENVIRONMENT == 'lando' || PANTHEON_ENVIRONMENT == 'dev'){
      //$showdebug = TRUE;
    //}

    $courses_json = $this->coursesApi->getCoursesByInstructor($semester, $netid);
    //$dump($courses_json);

    if (!empty($courses_json[0])) {
      foreach ($courses_json as $course_json) {
        $filtersubject = $course_json['subject'];
        if (!empty($filtersubject)){
          foreach ($subjects as $subject) {     
            //check each record for $netid
            if ($filtersubject === $subject) {
              $course_record = array(
                'subject' => $course_json['subject'], 
                'number' => $course_json['catalogNbr'], 
                'title' => $course_json['titleLong']
              );
                $person_course_record[] = $course_record;
            }           
          }
        }
      }
    }
    // remove duplicate entries from array
    $person_course_record = array_unique($person_course_record, SORT_REGULAR);
    if ($showdebug == TRUE) {
      dump('parse_person_courses_netid_prefix_json');
      dump($person_course_record);
    }
    return $person_course_record;
  }
}
