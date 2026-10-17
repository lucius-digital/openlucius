<?php

namespace Drupal\ol_main\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ol_main\Form\ColorSettingsForm;
use Drupal\ol_main\Form\GeneralSettingsForm;
use Drupal\ol_main\Form\HomeImageSettingsForm;
use Drupal\ol_main\Services\OlGlobalConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for main settings pages.
 */
class MainSettingsController extends ControllerBase {

  /**
   * The global configuration service.
   *
   * @var \Drupal\ol_main\Services\OlGlobalConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlGlobalConfig $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('olmain.global_config')
      );
  }

  /**
   * Return the home tabs settings page.
   *
   * @return array
   *   A renderable array for the settings page.
   */
  public function getHomeTabsSettings() {
    // Get general config form.
    $config_form = \Drupal::formBuilder()->getForm(GeneralSettingsForm::class);
    $config_tabs = $this->config->getConfigTabs();
    // Build it.
    $theme_vars = [
      'tab_content' => $config_form,
      'config_tabs' => $config_tabs,
    ];
    return [
      '#theme' => 'general_settings_page',
      '#vars' => $theme_vars,
      '#cache' => ['max-age' => 0],
      '#attached' => [
        'library' => [
          'ol_main/ol_sortable',
        ],
      ],
    ];
  }

  /**
   * Return the home image settings page.
   *
   * @return array
   *   A renderable array for the settings page.
   */
  public function getHomeImageSettings() {
    // Get general config form.
    $config_form = \Drupal::formBuilder()->getForm(HomeImageSettingsForm::class);
    $config_tabs = $this->config->getConfigTabs();
    // Build it.
    $theme_vars = [
      'tab_content' => $config_form,
      'config_tabs' => $config_tabs,
    ];
    return [
      '#theme' => 'general_settings_page',
      '#vars' => $theme_vars,
      '#cache' => ['max-age' => 0],
      '#attached' => [
        'library' => [
          'ol_main/ol_sortable',
        ],
      ],
    ];
  }

  /**
   * Return the color settings page.
   *
   * @return array
   *   A renderable array for the settings page.
   */
  public function getColorSettings() {
    // Get general config form.
    $config_form = \Drupal::formBuilder()->getForm(ColorSettingsForm::class);
    $config_tabs = $this->config->getConfigTabs();
    // Build it.
    $theme_vars = [
      'tab_content' => $config_form,
      'config_tabs' => $config_tabs,
    ];
    return [
      '#theme' => 'general_settings_page',
      '#vars' => $theme_vars,
      '#cache' => ['max-age' => 0],
      '#attached' => [
        'library' => [
          'ol_main/ol_color_picker',
        ],
      ],
    ];
  }

}
