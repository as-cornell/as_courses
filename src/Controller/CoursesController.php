<?php

namespace Drupal\as_courses\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class CoursesController.
 */
class CoursesController extends ControllerBase {

  public function content($semester) {

      $defaultsemester = \Drupal::config("as_courses.defaults")->get("defaultsemester");
      if (!empty($defaultsemester)) {
        // if no semester is in path value from router is set to SP14, so go get current semester from settings 
        if ($semester == 'SP24' OR empty($semester)){
            // set empty semester to current default semester
            $semester = $defaultsemester;
        }
      }


    return [
      '#theme' => 'courses',
      '#semester' => $semester
    ];

  }


}
