<?php

namespace Drupal\as_courses;

/**
 * extend Drupal's Twig_Extension class
 */
class getCurrentSemesters extends \Twig\Extension\AbstractExtension
{

  /**
   * {@inheritdoc}
   * Let Drupal know the name of custom extension
   */
  public function getName()
  {
    return 'as_courses.get.currentsemester';
  }


  /**
   * {@inheritdoc}
   * Return custom twig function to Drupal
   */
  public function getFunctions()
  {
    return [
      new \Twig\TwigFunction('get_current_semesters', [$this, 'get_current_semesters']),
    ];
  }

  /**
   * Parses semster key values into array
   *
   *
   * @return array $semesters
   *   string for labels
   */
  public function get_current_semesters()
  {
    

    $config = \Drupal::config("as_courses.defaults")->get("semester");
    if (!empty($config)) {
      // filter out unchecked items
      $semesters = array_filter($config);

    }

    return $semesters;
  }
}
