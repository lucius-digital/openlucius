<?php

namespace Drupal\ol_main\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ol_main\Services\OlGlobalConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MainSettingsController.
 */
class MainSettingsController extends ControllerBase {

  /**
   * @var $config
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
   * @return array
   */
  public function getHomeTabsSettings(){
    // Get general config form.
    $config_form = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\GeneralSettingsForm::class);
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
  /**
   * @return array
   */
  public function getHomeImageSettings(){
    // Get general config form.
    $config_form = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\HomeImageSettingsForm::class);
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
   * @return array
   */
  public function getColorSettings(){
    // Get general config form.
    $config_form = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\ColorSettingsForm::class);
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
