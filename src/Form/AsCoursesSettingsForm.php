<?php

namespace Drupal\as_courses\Form;

use Drupal\as_courses\Service\SemesterGeneratorService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for AS Courses default settings.
 */
class AsCoursesSettingsForm extends ConfigFormBase {

  /**
   * The semester generator service.
   *
   * @var \Drupal\as_courses\Service\SemesterGeneratorService
   */
  protected $semesterGenerator;

  /**
   * Constructs an AsCoursesSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\as_courses\Service\SemesterGeneratorService $semester_generator
   *   The semester generator service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    SemesterGeneratorService $semester_generator
  ) {
    parent::__construct($config_factory);
    $this->semesterGenerator = $semester_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('as_courses.semester_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['as_courses.defaults'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'as_courses_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('as_courses.defaults');

    // Get dynamic semester options from the semester generator service.
    $semester_options = $this->semesterGenerator->getSemesterOptions();

    $form['semester'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Semesters to display in tabs.'),
      '#description' => $this->t('Will only display tabs if more than one semester is selected.'),
      '#options' => $semester_options,
      '#default_value' => $config->get('semester') ?: [],
    ];

    $form['defaultsemester'] = [
      '#type' => 'select',
      '#title' => $this->t('Default semester to display at /courses.'),
      '#options' => $semester_options,
      '#default_value' => $config->get('defaultsemester'),
    ];

  // Course prefixes in case there's no prefixes via department theme settings, for example on AS
  $form['course_prefixes'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Course Prefixes'),
    '#default_value' => $config->get('course_prefixes'),
    '#description'   => t("Comma separated list of course prefixes to pass to API. Example: PSYCH,ECON,HIST"),
  );

    return parent::buildForm($form,$form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('as_courses.defaults')
      ->set('semester', $form_state->getValue('semester'))
      ->set('defaultsemester', $form_state->getValue('defaultsemester'))
      ->set('course_prefixes', $form_state->getValue('course_prefixes'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
