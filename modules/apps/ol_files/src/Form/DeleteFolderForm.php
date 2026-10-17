<?php

namespace Drupal\ol_files\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_files\Services\OlFolders;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting a folder.
 */
class DeleteFolderForm extends FormBase {

  /**
   * Folders service.
   *
   * @var \Drupal\ol_files\Services\OlFolders
   */
  protected $folders;

  /**
   * DeleteFolderForm constructor.
   *
   * @param \Drupal\ol_files\Services\OlFolders $folders
   *   Folders service.
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
    return 'ol_delete_folder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['folder_id'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => ['id' => ['remove-folder-id']],
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
    $folder_id = $form_state->getValue('folder_id');
    $this->folders->removeFolder($folder_id);
  }

}
