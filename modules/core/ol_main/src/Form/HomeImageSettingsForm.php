<?php

namespace Drupal\ol_main\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGlobalConfig;
use Drupal\ol_main\Services\OlGroups;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class HomeImageSettingsForm.
 */
class HomeImageSettingsForm extends FormBase {

  /**
   * @var $config
   */
  protected $config;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_main\Services\OlGlobalConfig $config
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct(OlGlobalConfig $config, OlFiles $files, OlGroups $groups) {
    $this->config = $config;
    $this->files = $files;
    $this->groups = $groups;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.global_config'),
      $container->get('olmain.files'),
      $container->get('olmain.groups')
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

    $global_group_id = $this->groups->getGlobalGroupId();
    $default_fid = $this->config->getHomeHeaderImage();
    $default_fid = ($default_fid) ? $default_fid : '';
    $hdd_file_location = $this->files->buildFileLocaton('home_image', $global_group_id);

    $form['file'] = array(
      '#prefix' => '<div class="form-group p-5">',
      '#title' => t('Home header image'),
      '#type' => 'managed_file',
      '#description' => t('Preferably 1280x200, otherwise image will get cropped from the middle'),
      '#required' => FALSE,
      '#default_value' => [$default_fid],
      '#upload_location' => 'private://'.$hdd_file_location,
      '#progress_indicator' => 'bar',
      '#progress_message' => t('Please wait...'),
      '#multiple' => FALSE,
      '#upload_validators' => array(
        'file_validate_extensions' => $this->files->getAllowedImageExtentions(),
      ),
      '#theme' => 'image_widget',
      '#preview_image_style' => '50x50',
      '#attributes' => [
        'class' => array('form-control')
      ],
      '#suffix' => '</div>'
    );

    $form['submit'] = [
      '#prefix' => '<div class="mt-4 border-top pt-4"><div>',
      '#type' => 'submit',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => t('Save'),
      '#suffix' => '</span>'
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle files.
    $file = $form_state->getValue('file');
    $current_header_fid = $this->config->getHomeHeaderImage();
    $global_gid = $this->groups->getGlobalGroupId();
    // If there is no file, remove existing (if any).
    if(empty($file[0])){
      $this->removeExistingHeaderFile();
    }
    // Check if there is a change in file, if so: remove existing and save new.
    elseif ($current_header_fid != $file[0]){
      $this->removeExistingHeaderFile();
      $this->files->saveFiles($file, 'home_header',null, null, null, $global_gid );
    }
  }
  /**
   * Remove existing file.
   */
  private function removeExistingHeaderFile() {
    $current_header_fid = $this->config->getHomeHeaderImage();
    if ($current_header_fid) {
      $this->files->removeOlFileAndFile($current_header_fid, FALSE);
    }
  }

}
