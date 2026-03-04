<?php

namespace Drupal\as_courses;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Twig extension for getting current semesters.
 */
class getCurrentSemesters extends \Twig\Extension\AbstractExtension implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a getCurrentSemesters object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

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


    $config = $this->configFactory->get("as_courses.defaults")->get("semester");
    if (!empty($config)) {
      // filter out unchecked items
      $semesters = array_filter($config);

    }

    return $semesters;
  }
}
