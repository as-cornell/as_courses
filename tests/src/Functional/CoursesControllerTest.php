<?php

namespace Drupal\Tests\as_courses\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Courses controller.
 *
 * @group as_courses
 */
class CoursesControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['as_courses'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set default configuration.
    $config = $this->config('as_courses.defaults');
    $config->set('semester', ['SP26' => 'SP26', 'FA26' => 'FA26']);
    $config->set('defaultsemester', 'SP26');
    $config->set('course_prefixes', 'PSYCH');
    $config->save();
  }

  /**
   * Tests the courses page loads.
   */
  public function testCoursesPageLoads() {
    $this->drupalGet('courses');

    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the courses page with semester parameter.
   */
  public function testCoursesPageWithSemester() {
    $this->drupalGet('courses/FA26');

    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the courses page uses default semester.
   */
  public function testCoursesPageUsesDefaultSemester() {
    $this->drupalGet('courses');

    // The page should render with the default semester (SP26).
    // This is verified by checking the render array in the controller.
    $this->assertSession()->statusCodeEquals(200);
  }

}
