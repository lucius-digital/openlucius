<?php

namespace Drupal\ol_main\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_board\Services\OlTasks;
use Drupal\ol_main\Services\OlGlobalConfig;
use Drupal\ol_main\Services\OlGroups;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class ColorSettingsForm.
 */
class ColorSettingsForm extends FormBase {

  /**
   * @var $config
   */
  protected $config;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_main\Services\OlGroups $groups
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_group_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $color_settings = $this->config->getColorSettings();
    $color_settings = (empty($color_settings)) ?  $this->config->getDefaultColors() : $color_settings ;

    // Build form.
    $form['nav_color'] = [
      '#prefix' => '<div>',
      '#type' => 'textfield',
      '#default_value' => $color_settings->nav,
      '#title' => t('Main Navigation'),
      '#attributes' => [
        'data-disable-refocus' => 'true',
        'data-huebee' => ' {"notation": "hex"}',
        'class' => array('form-control', 'color-picker'),
        'onsubmit' => 'return false',
      ],
      '#suffix' => '</span></div>'
    ];
    $form['global_background'] = [
      '#prefix' => '<div class="mt-3 ">',
      '#type' => 'textfield',
      '#default_value' => $color_settings->global_background,
      '#title' => t('Global Background'),
      '#attributes' => [
        'data-disable-refocus' => 'true',
        'data-huebee' => ' {"notation": "hex"}',
        'class' => array('form-control', 'color-picker'),
        'onsubmit' => 'return false',
      ],
      '#suffix' => '</span></div>'
    ];
    $form['submit'] = [
      '#prefix' => '<div class="mt-4 border-top pt-4"><div>',
      '#type' => 'submit',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => t('Save'),
      '#suffix' => '</span>'
    ];
    $form['set_defaults'] = [
      '#prefix' => '<span>',
      '#type' => 'button',
      '#value' => t('Defaults'),
      '#attributes' => [
        'data-disable-refocus' => 'true',
        'class' => array('btn btn-light'),
        'onsubmit' => 'return false',
      ],
      '#ajax' => [
        'callback' => '::callbackSetDefaults',
        'event' => 'click',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Please wait...'),
        ],
      ],
      '#suffix' => '</span></div>'
    ];
    return $form;
  }

  public function callbackSetDefaults(array &$form, FormStateInterface $form_state) {
    // Initiate.
    $response = new AjaxResponse();
    // Get defaults.
    $color_settings = $this->config->getDefaultColors();
    // Change inputs to default colors.
    $response->addCommand(new InvokeCommand('#edit-nav-color', 'val',[$color_settings->nav]));
    $response->addCommand(new InvokeCommand('#edit-global-background', 'val',[$color_settings->global_background]));
    // Clear messages and return response.
    \Drupal::messenger()->deleteAll();
     return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nav_color = Html::escape($form_state->getValue('nav_color'));
    $global_background = Html::escape($form_state->getValue('global_background'));
    $colors = [];
    $colors['nav'] = $nav_color;
    $colors['global_background'] = $global_background;
    $this->config->updateColors($colors);
  }

}
