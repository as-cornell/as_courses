<?php

namespace Drupal\as_courses;

/**
 * extend Drupal's Twig_Extension class
 */
class parseSemesterName extends \Twig\Extension\AbstractExtension
{

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
      // apply replace patterns
      //dump($semester);
      $semester_name = $semester;
      $semester_name = str_replace('SP','Spring ',$semester_name);
      $semester_name = str_replace('SU','Summer ',$semester_name);
      $semester_name = str_replace('FA','Fall ',$semester_name);
      $semester_name = str_replace('WI','Winter ',$semester_name);
      $semester_name = str_replace('24','2024',$semester_name);
      $semester_name = str_replace('25','2025',$semester_name);
      $semester_name = str_replace('26','2026',$semester_name);
      $semester_name = str_replace('27','2027',$semester_name);

    }
    return $semester_name;
  }
}
