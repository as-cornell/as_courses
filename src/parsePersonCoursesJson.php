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
    $person_course_record = [];
    $filternetid = '';

    $courses_json = as_courses_get_courses_json($semester,$keyword_params);

    if (!empty($courses_json)) {
      foreach ($courses_json as $course_data) {
        $enrollgroups = $course_data['enrollGroups'];
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
                            'subject' => $course_data['subject'], 
                            'number' => $course_data['catalogNbr'], 
                            'title' => $course_data['titleLong']
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
    return $person_course_record;
  }
}
