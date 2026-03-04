<?php

namespace Drupal\Tests\as_courses\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the AS Courses settings form.
 *
 * @group as_courses
 */
class AsCoursesSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['as_courses'];

  /**
   * A user with permission to administer AS Courses settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user with admin permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
    ]);
  }

  /**
   * Tests the settings form renders.
   */
  public function testSettingsFormRenders() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/as_courses');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Semesters to display in tabs');
    $this->assertSession()->pageTextContains('Default semester to display at /courses');
    $this->assertSession()->pageTextContains('Course Prefixes');
  }

  /**
   * Tests the form displays dynamic semester options.
   */
  public function testDynamicSemesterOptions() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/as_courses');

    // Check that semester options are present (should be dynamically generated).
    // Current year is 2026, so with default offsets (-1 to +5),
    // we should see semesters from 2025 to 2031.
    $this->assertSession()->pageTextContains('Fall 2025');
    $this->assertSession()->pageTextContains('Spring 2026');
    $this->assertSession()->pageTextContains('Fall 2031');

    // Old hardcoded semesters should not appear beyond the range.
    $this->assertSession()->pageTextNotContains('Fall 2023');
  }

  /**
   * Tests the form saves configuration.
   */
  public function testFormSavesConfiguration() {
    $this->drupalLogin($this->adminUser);

    // Submit the form with some semester selections.
    $edit = [
      'semester[SP26]' => 'SP26',
      'semester[FA26]' => 'FA26',
      'defaultsemester' => 'SP26',
      'course_prefixes' => 'PSYCH,ECON,HIST',
    ];
    $this->drupalGet('admin/config/content/as_courses');
    $this->submitForm($edit, 'Save configuration');

    // Check success message.
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Verify config was saved.
    $config = $this->config('as_courses.defaults');
    $saved_semesters = $config->get('semester');
    $this->assertContains('SP26', $saved_semesters);
    $this->assertContains('FA26', $saved_semesters);
    $this->assertEquals('SP26', $config->get('defaultsemester'));
    $this->assertEquals('PSYCH,ECON,HIST', $config->get('course_prefixes'));
  }

}
