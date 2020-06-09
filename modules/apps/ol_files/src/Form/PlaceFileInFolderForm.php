<?php

namespace Drupal\ol_files\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_files\Services\OlFolders;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class PlaceFileInFolderForm.
 */
class PlaceFileInFolderForm extends FormBase {


  /**
   * @var $folders
   */
  protected $folders;

  /**
   * Class constructor.
   * @param \Drupal\ol_main\Services\OlFiles $files
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
    return 'place_file_in_folder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['id'] = [
      '#type' => 'hidden',
      '#attributes' => array('id' => array('edit-fid')),
    ];
    $form['folder_id'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'select',
      '#weight' => '0',
      '#title' => t('Choose a folder'),
      '#default_value' => '0',
      '#options' => $this->folders->getFoldersInCurrentGroup(),
      '#attributes' => array('class' => array('form-control')),
      '#suffix' => '</div>'
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
    // Get vars.
    $id = Html::escape($form_state->getValue('id'));
    $folder_id = Html::escape($form_state->getValue('folder_id'));
    // Place in folder.
    $this->folders->placeFileInFolder($folder_id, $id);

  }


}


