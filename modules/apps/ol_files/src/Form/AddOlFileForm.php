<?php

namespace Drupal\ol_files\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_files\Services\OlFolders;
use Drupal\ol_main\Services\OlFiles;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class AddOlFileForm.
 */
class AddOlFileForm extends FormBase {

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $folders
   */
  protected $folders;

  /**
   * Class constructor.
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct(OlFiles $files, OlFolders $folders) {
    $this->files = $files;
    $this->folders = $folders;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.files'),
      $container->get('olfiles.folders')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ol_add_file_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Data.
    $folder_id = Html::escape(\Drupal::request()->query->get('folder'));
    $default_folder = (is_numeric($folder_id)) ? $folder_id : null;
    $hdd_file_location = $this->files->buildFileLocaton('file');

    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="modal-body"><div class="row"><div class="col-12 col-md-12"><div class="form-group">',
      '#allowed_tags' => ['div'],
      '#weight' => '9',
    ];
    $form['files'] = array(
      '#title' => t('Upload files'),
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#upload_location' => 'private://'.$hdd_file_location,
      '#multiple' => TRUE,
      '#upload_validators' => array(
        'file_validate_extensions' => $this->files->getAllowedFileExtentions(),
      ),
      '#weight' => '10',
    );
    $form['markup_2'] = [
      '#type' => 'markup',
      '#markup' => '</div></div></div>',
      '#allowed_tags' => ['div'],
      '#weight' => '11',
    ];
    $form['id_folder'] = [
      '#prefix' => '<div class="row"><div class="col-12 col-md-6"><div class="form-group">',
      '#type' => 'select',
      '#weight' => '20',
      '#title' => t('Place new files in folder'),
      '#default_value' => $default_folder,
      '#options' => $this->folders->getFoldersInCurrentGroup(),
      '#attributes' => array('class' => array('form-control')),
      '#suffix' => '</div></div></div>'
    ];
    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '30',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $this->t('Submit'),
      '#suffix' => '</div>'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form data and save.
    $files = $form_state->getValue('files');
    $id_folder = $form_state->getValue('id_folder');
    $this->files->saveFiles($files, 'file',null, $id_folder, true);
  }

}


