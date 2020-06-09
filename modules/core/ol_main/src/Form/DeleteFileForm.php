<?php

namespace Drupal\ol_main\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_main\Services\OlFiles;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class DeleteFileForm.
 */
class DeleteFileForm extends FormBase {

  /**
   * @var $files
   */
  protected $files;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_main\Services\OlFiles $files
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
      '#attributes' => array('id' => array('remove-file-id')),
    ];
    $form['file_type'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => array('id' => array('file-type')),
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<button type="button" class="btn btn-default" data-dismiss="modal">' .t('Cancel').'</button>',
      '#allowed_tags' => ['button'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => '20',
      '#attributes' => array('class' => array('btn btn-danger')),
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
      $this->files->removeOlFileAndFile($fid, true);
    }
    elseif ($file_type == 'text_doc') {
      $this->files->removeOlFileAndTextDoc($ol_file_id, true);
    }
  }

}
