<?php

namespace Drupal\as_courses;

/**
 * extend Drupal's Twig_Extension class
 */
class parsePersonCoursesNetidPrefixJson extends \Twig\Extension\AbstractExtension
{

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
    //if (PANTHEON_ENVIRONMENT == 'lando' || PANTHEON_ENVIRONMENT == 'dev'){
      //$showdebug = TRUE;
    //}

    $courses_json = as_courses_get_courses_netid_json($semester,$netid);
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
