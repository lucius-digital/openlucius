<?php

namespace Drupal\ol_text_docs\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_text_docs\Services\OlTextDocs;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating and editing text documents.
 */
class TextDocForm extends FormBase {

  /**
   * Text docs service.
   *
   * @var \Drupal\ol_text_docs\Services\OlTextDocs
   */
  protected $textdocs;

  /**
   * Files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * TextDocForm constructor.
   *
   * @param \Drupal\ol_text_docs\Services\OlTextDocs $text_docs
   *   The text docs service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   The files service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   The members service.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   The groups service.
   */
  public function __construct(OlTextDocs $text_docs, OlFiles $files, OlMembers $members, OlGroups $groups) {
    $this->textdocs = $text_docs;
    $this->files = $files;
    $this->members = $members;
    $this->groups = $groups;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oltextdocs.textdocs'),
      $container->get('olmain.files'),
      $container->get('olmembers.members'),
      $container->get('olmain.groups')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'text_doc_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = NULL, $id = NULL) {

    // Defaults.
    $name = '';
    $body = '';
    $button_text = t('Save Notebook');
    $hdd_file_location = $this->files->buildFileLocaton('text_doc');
    $num_users = $this->members->countMembers(NULL, TRUE);
    $send_mail_title = ['1' => t('Notify other group members') . ' (' . $num_users . ')'];

    // Handle edit vars.
    if ($op == 'edit') {
      $message_data = $this->getTextDocData($id);
      $name = $message_data->name;
      $body = $message_data->body;
      $button_text = t('Update Notebook');
    }
    // Build form.
    $form['doc_id'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
      '#weight' => '0',
    ];
    $form['name'] = [
    // '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#prefix' => '<div class=""><div class="form-group">',
      '#type' => 'textfield',
      '#weight' => '10',
      '#default_value' => $name,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => t('Add a title...'), 'class' => ['form-control'], 'maxlength' => '150'],
      '#suffix' => '</div>',
    ];
    $form['body'] = [
      '#prefix' => '<div class="form-group">',
      '#type' => 'textarea',
      '#attributes' => [
        'class' => ['summernote'],
      ],
      '#weight' => '20',
      '#default_value' => $body,
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];
    $form['body_old'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'class' => ['hidden'],
      ],
      '#weight' => '22',
      '#default_value' => $body,
    ];
    $form['send_mail'] = [
      '#prefix' => '<div class="row"><div class="col-12 col-md-6 pl-4 small text-muted pb-2">',
      '#type' => 'checkboxes',
      '#options' => $send_mail_title,
      '#default_value' => ['0'],
      '#weight' => '25',
      '#attributes' => [
        'data-toggle' => 'toggle',
        'data-onstyle' => 'success',
        'data-size' => 'xs',
      ],
      '#suffix' => '</div>',
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-12 col-md-6 small">',
      '#allowed_tags' => ['div'],
      '#weight' => '30',
    ];
    $form['files'] = [
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#upload_location' => 'private://' . $hdd_file_location,
      '#multiple' => TRUE,
      '#progress_indicator' => 'bar',
      '#progress_message' => t('Please wait...'),
      '#upload_validators' => [
        'FileExtension' => $this->files->getAllowedFileExtentions(),
      ],
      '#weight' => '35',
    ];
    $form['markup_2'] = [
      '#type' => 'markup',
      '#markup' => '</div></div>',
      '#allowed_tags' => ['div'],
      '#weight' => '40',
    ];

    $form['cancel'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'button',
      '#weight' => '90',
      '#attributes' => [
        'data-disable-refocus' => 'true',
        'class' => ['btn btn-light'],
        'onsubmit' => 'return false',
      ],
      '#value' => t('Cancel'),
      '#suffix' => '',
    ];
    $form['submit'] = [
      '#prefix' => '',
      '#type' => 'submit',
      '#weight' => '100',
      '#attributes' => ['class' => ['btn btn-success']],
      '#value' => $button_text,
      '#suffix' => '</div>',
    ];
    // For @-mentions.
    $group_users = $this->members->getUsersNamesInGroupFlatArray();
    $form['#attached']['library'][] = 'ol_main/summernote_inc_init';
    $form['#attached']['drupalSettings']['group_users'] = $group_users;
    // For uploading inline files.
    $group_uuid = $this->groups->getGroupUuidById();
    $form['#attached']['drupalSettings']['group_uuid'] = $group_uuid;
    // Return form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (strlen($form_state->getValue('name')) > 50) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('name', $this->t('Notebook not saved yet: title can not be more than 50 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get data.
    $id = Html::escape($form_state->getValue('doc_id'));
    $name = Xss::filter($form_state->getValue('name'));
    $body = Xss::filter($form_state->getValue('body'), getAllowedHTMLTags());
    $body = sanatizeSummernoteInput($body);
    $send_mail = $form_state->getValue('send_mail')[1];
    $files = $form_state->getValue('files');
    // Existing, update text doc.
    if (is_numeric($id)) {
      // Update doc.
      $this->textdocs->updateTextDoc($id, $name, $body, $send_mail);
      // Remove files that are deleted.
      $body_old = Xss::filter($form_state->getValue('body_old'), getAllowedHTMLTags());
      $this->files->deleteInlineFile($body_old, $body);
    }
    // New, save text doc.
    elseif (empty($id)) {
      $id = $this->textdocs->saveTextDoc($name, $body, $send_mail);
    }
    if (!empty($files)) {
      $this->files->saveFiles($files, 'text_doc_attachment', $id);
    }
  }

  /**
   * Get text document data from database.
   *
   * @param int $id
   *   The text document ID.
   *
   * @return object
   *   The text document data object.
   */
  private function getTextDocData($id) {
    $query = \Drupal::database()->select('ol_text_doc', 'olt');
    $query->addField('olt', 'body');
    $query->addField('olt', 'name');
    $query->condition('olt.id', $id);
    return $query->execute()->fetchObject();
  }

}
