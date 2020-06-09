<?php

namespace Drupal\ol_files\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_files\Services\OlFolders;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class RemoveFileFromFolderForm.
 */
class RemoveFileFromFolderForm extends FormBase {


  /**
   * @var $folders
   */
  protected $folders;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_files\Services\OlFolders $folders
   */
  public function __construct(OlFolders $folders) {
    $this->folders = $folders;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olfiles.folders')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remove_file_from_folder';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['id'] = [
      '#type' => 'hidden',
      '#attributes' => array('id' => array('edit-file-id')),
    ];
    $form['submit'] = [
      '#prefix' => '<div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '30',
      '#attributes' => array('class' => array('btn btn-warning')),
      '#value' => $this->t('Yes, remove file from folder'),
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
    // Get vars.
    $id_file = Html::escape($form_state->getValue('id'));
    $this->folders->removeFileFromFolder($id_file);
  }

}


