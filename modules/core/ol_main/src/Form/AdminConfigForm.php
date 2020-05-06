<?php

namespace Drupal\ol_main\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class AdminConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'ol_main.admin_settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'olmain_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['nodejs_server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node.js server address'),
      '#default_value' => $config->get('nodejs_server_url'),
      '#description' => t('For example, local default: http://localhost:3000'),
    ];
    $title = array( '1' => t('Show admin toolbar in frontend theme'));
    $default = array($config->get('show_admin_toolbar'));
    $default = (is_null($default)) ? array('0') : $default; // Needed for first time form load.
    $form['show_toolbar'] = array(
      '#title' => t('Show admin toolbar'),
      '#type' => 'checkboxes',
      '#options' => $title,
      '#default_value' => $default,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('nodejs_server_url', $form_state->getValue('nodejs_server_url'))
      ->set('show_admin_toolbar', $form_state->getValue('show_toolbar')[1])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
