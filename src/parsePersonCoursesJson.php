<?php

namespace Drupal\as_courses;

/**
 * extend Drupal's Twig_Extension class
 */
class parsePersonCoursesJson extends \Twig\Extension\AbstractExtension
{

  /**
   * {@inheritdoc}
   * Let Drupal know the name of custom extension
   */
  public function getName()
  {
    return 'as_courses.parse.person.json';
  }


  /**
   * {@inheritdoc}
   * Return custom twig function to Drupal
   */
  public function getFunctions()
  {
    return [
      new \Twig\TwigFunction('parse_person_courses_json', [$this, 'parse_person_courses_json']),
    ];
  }

  /**
   * Parses a single person's courses JSON data into array for theming
   *
   *
   * @return array $person_course_record
   *   data in array for theming
   */
  public function parse_person_courses_json($semester,$keyword_params,$netid)
  {
    $courses_json = [];
    $person_course_record = [];
    $filternetid = '';
    $course_record = [];
    $showdebug = '';
    //if (PANTHEON_ENVIRONMENT == 'lando' || PANTHEON_ENVIRONMENT == 'dev'){
      //$showdebug = TRUE;
    //}


    $courses_json = as_courses_get_courses_json($semester,$keyword_params);
    //$dump($courses_json);

    if (!empty($courses_json)) {
      foreach ($courses_json as $course_json) {
        //$dump($course_data['data']['classes']);
        $enrollgroups = $course_json['enrollGroups'];
        if (!empty($enrollgroups)){
            foreach ($enrollgroups as $enrollgroup) {
              foreach ($enrollgroup['classSections'] as $section) {
                foreach ($section['meetings'] as $meeting) {
                  foreach ($meeting['instructors'] as $instructors) {
                    //dump($instructors);
                    if (!empty($instructors['netid'])){
                      $filternetid = $instructors['netid'];
       
                        //check each record for $netid
                        if ($filternetid === $netid) {
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
              }
            }
        }
    }
    // remove duplicate entries from array
    $person_course_record = array_unique($person_course_record, SORT_REGULAR);
    if ($showdebug == TRUE) {
      dump('parse_person_courses_json');
      dump($person_course_record);
    }
    return $person_course_record;
  }
}
