<?php

namespace Drupal\ol_main\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_main\Services\OlFiles;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting files.
 */
class DeleteFileForm extends FormBase {

  /**
   * The OL files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * Constructs a DeleteFileForm instance.
   *
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   The OL files service.
   */
  public function __construct(OlFiles $files) {
    $this->files = $files;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('olmain.files')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ol_delete_file_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['file_id'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => ['id' => ['remove-file-id']],
    ];
    $form['file_type'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => ['id' => ['file-type']],
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<button type="button" class="btn btn-default" data-dismiss="modal">' . t('Cancel') . '</button>',
      '#allowed_tags' => ['button'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => '20',
      '#attributes' => ['class' => ['btn btn-danger']],
      '#value' => t('Yes, Delete Permanently'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get vars.
    $ol_file_id = $form_state->getValue('file_id');
    $file_type = $form_state->getValue('file_type');
    if ($file_type == 'file') {
      $fid = $this->files->getFileId($ol_file_id);
      $this->files->removeOlFileAndFile($fid, TRUE);
    }
  }

}
