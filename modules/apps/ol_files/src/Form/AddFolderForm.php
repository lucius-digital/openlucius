<?php

namespace Drupal\ol_files\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_files\Services\OlFolders;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class AddFolderForm.
 */
class AddFolderForm extends FormBase {

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
    return 'add_folder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['folder_id'] = [
      '#type' => 'hidden',
      '#attributes' => array('id' => array('edit-folder-id')),
    ];

    $form['name'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'textfield',
      '#weight' => '0',
      '#required' => true,
      '#attributes' => array('placeholder' => t('Add a folder name...'), 'class' => array('form-control')),
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
    parent::validateForm($form, $form_state);
    $name = Html::escape($form_state->getValue('name'));
    if (strlen($name) < 2) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('name', $this->t('Folder name must be at least 2 characters long.'));
    }
    if (strlen($name) > 35) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('name', $this->t('Folder name can be no longer than 35 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get data.
    $name = Html::escape($form_state->getValue('name'));
    $folder_id = Html::escape($form_state->getValue('folder_id'));
    // Update or add.
    if(is_numeric($folder_id)){
      // Update Folder.
      $this->folders->updateFolder($name, $folder_id);
      \Drupal::messenger()->addStatus(t('Your folder was successfully updated to ' .$name));
    } else {
      // Add New Folder.
      $this->folders->saveFolder($name);
      \Drupal::messenger()->addStatus(t('Your folder was added successfully.'));
    }

  }

}


