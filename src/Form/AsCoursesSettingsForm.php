<?php
namespace Drupal\as_courses\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\MapArray;


class AsCoursesSettingsForm extends ConfigFormBase {

    /**
    *array An array of configuration object names that are editable
	*/
   protected function getEditableConfigNames() {
   return ['as_courses.defaults'];
  }

   public function getFormID() {
    return 'as_courses_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('as_courses.defaults');  //since we are extending ConfigFormBase instead of FormBase, it gives use access to the config object



    $form['semester'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Semesters to display in tabs.'),
    '#options' => array(
     'FA23' => t('Fall 2023'),
     'WI24' => t('Winter 2024'),
     'SP24' => t('Spring 2024'),
     'SU24' => t('Summer 2024'),
     'FA24' => t('Fall 2024'),
     'WI25' => t('Winter 2025'),
     'SP25' => t('Spring 2025'),
     'SU25' => t('Summer 2025'),
     'FA25' => t('Fall 2025'),
     'WI26' => t('Winter 2026'),
     'SP26' => t('Spring 2026'),
     'SU26' => t('Summer 2026'),
     'FA26' => t('Fall 2026'),
     'WI27' => t('Winter 2027'),
     'SP27' => t('Spring 2027'),
     'SU27' => t('Summer 2027'),
     'FA27' => t('Fall 2027'),
   ),
   '#default_value' => $config->get('semester')
  );

    $form['defaultsemester'] = array(
    '#type' => 'select',
    '#title' => t('Default semester to display at /courses.'),
    '#options' => array(
     'FA23' => t('Fall 2023'),
     'SP24' => t('Spring 2024'),
     'SU24' => t('Summer 2024'),
     'FA24' => t('Fall 2024'),
     'SP25' => t('Spring 2025'),
     'SU25' => t('Summer 2025'),
     'FA25' => t('Fall 2025'),
     'SP26' => t('Spring 2026'),
     'SU26' => t('Summer 2026'),
     'FA26' => t('Fall 2026'),
     'SP27' => t('Spring 2027'),
     'SU27' => t('Summer 2027'),
     'FA27' => t('Fall 2027'),
   ),
   '#default_value' => $config->get('defaultsemester')
  );

  // Course prefixes in case there's no prefixes via department theme settings
  $form['course_prefixes'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Course Prefixes'),
    '#default_value' => $config->get('course_prefixes'),
    '#description'   => t("Comma separated list of course prefixes to pass to API. Example: PSYCH,ECON,HIST"),
  );

    return parent::buildForm($form,$form_state);
  }

  /**
   * Form submission handler.
   *
   *  $form -> An associative array containing the structure of the form.
   *  $form_state -> An associative array containing the current state of the form.
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('as_courses.defaults')
      ->set('semester', $form_state->getValue('semester'))
      ->set('defaultsemester', $form_state->getValue('defaultsemester'))
      ->set('course_prefixes', $form_state->getValue('course_prefixes'))
      ->set('sort', $form_state->getValue('sort'))
      ->save();
  }
}
