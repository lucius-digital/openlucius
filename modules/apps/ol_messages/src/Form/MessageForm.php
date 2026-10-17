<?php

namespace Drupal\ol_messages\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_messages\Services\OlMessages;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating and editing messages.
 */
class MessageForm extends FormBase {

  /**
   * Messages service.
   *
   * @var \Drupal\ol_messages\Services\OlMessages
   */
  protected $messages;

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
   * MessageForm constructor.
   *
   * @param \Drupal\ol_messages\Services\OlMessages $messages
   *   Messages service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   Files service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   Groups service.
   */
  public function __construct(OlMessages $messages, OlFiles $files, OlMembers $members, OlGroups $groups) {
    $this->messages = $messages;
    $this->files = $files;
    $this->members = $members;
    $this->groups = $groups;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmessages.messages'),
      $container->get('olmain.files'),
      $container->get('olmembers.members'),
      $container->get('olmain.groups')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = NULL, $id = NULL) {

    // Defaults.
    $name = '';
    $body = '';
    $button_text = t('Submit');
    $hdd_file_location = $this->files->buildFileLocaton('message');
    $mail_send_default = ['1'];
    $num_users = $this->members->countMembers(NULL, TRUE);
    $send_mail_title = [
      '1' => t('Notify all group members') . ' (' . $num_users . ')',
    ];

    // Handle edit vars.
    if ($op == 'edit') {
      $message_data = $this->getMessageData($id);
      $name = $message_data->name;
      $body = $message_data->body;
      $button_text = t('Update Message');
      $mail_send_default = ['0'];
    }

    // Build form.
    $form['message_id'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
      '#weight' => '0',
    ];
    $form['name'] = [
      '#prefix' => '<div class="modal-body"><div class="form-group">',
      '#type' => 'textfield',
      '#weight' => '10',
      '#default_value' => $name,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => t('Add a title...'),
        'class' => ['form-control'],
        'maxlength' => '150',
      ],
      '#suffix' => '</div>',
    ];
    $form['body_old'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'class' => ['hidden'],
      ],
      '#weight' => '21',
      '#default_value' => $body,
    ];
    $form['body'] = [
      '#prefix' => '<div class="form-group message-body">',
      '#type' => 'textarea',
      '#attributes' => [
        'class' => ['summernote'],
      ],
      '#weight' => '20',
      '#default_value' => $body,
      '#required' => TRUE,
      '#suffix' => '</div>',
    ];
    $form['send_mail'] = [
      '#prefix' => '<div class="row"><div class="col-12 col-md-6 pl-4 small text-muted pb-2">',
      '#type' => 'checkboxes',
      '#options' => $send_mail_title,
      '#default_value' => $mail_send_default,
      '#attributes' => [
        'data-toggle' => 'toggle',
        'data-onstyle' => 'success',
        'data-size' => 'xs',
      ],
      '#weight' => '25',
      '#suffix' => '</div>',
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-12 col-md-6">',
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
      '#attributes' => [
        'class' => ['small text-muted pl-md-3'],
      ],
      '#weight' => '35',
    ];
    $form['markup_2'] = [
      '#type' => 'markup',
      '#markup' => '</div></div>',
      '#allowed_tags' => ['div'],
      '#weight' => '40',
    ];

    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
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

    if (strlen($form_state->getValue('name')) > 128) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('name', $this->t('Message not saved yet: title can\'t be more than 128 characters.'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get data.
    $id = Html::escape($form_state->getValue('message_id'));
    $name = Xss::filter($form_state->getValue('name'));
    $body = Xss::filter($form_state->getValue('body'), getAllowedHTMLTags());
    $body = sanatizeSummernoteInput($body);
    $send_mail = $form_state->getValue('send_mail')[1];
    $files = $form_state->getValue('files');
    // Existing, update message.
    if (is_numeric($id)) {
      $this->messages->updateMessage($id, $name, $body, $send_mail);
      // Remove files that are deleted.
      $body_old = Xss::filter($form_state->getValue('body_old'), getAllowedHTMLTags());
      $this->files->deleteInlineFile($body_old, $body);
    }
    // New, save message.
    elseif (empty($id)) {
      $id = $this->messages->saveMessage($name, $body, $send_mail);
    }
    if (!empty($files)) {
      $this->files->saveFiles($files, 'message', $id);
    }
  }

  /**
   * Retrieve message data by ID.
   *
   * @param int $id
   *   The message ID.
   *
   * @return object
   *   The message object with name and body fields.
   */
  private function getMessageData($id) {
    $query = \Drupal::database()->select('ol_message', 'mess');
    $query->addField('mess', 'body');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $id);
    return $query->execute()->fetchObject();
  }

}


