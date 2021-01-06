<?php

namespace Drupal\ol_messages\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_messages\Services\OlCultureQuestions;
use Drupal\ol_messages\Services\OlMessages;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class MessageForm.
 */
class MessageForm extends FormBase {

  /**
   * @var $messages
   */
  protected $messages;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $members
   */
  protected $members;

  /**
   * Class constructor.
   */
  public function __construct(OlMessages $messages, OlFiles $files, OlMembers $members) {
    $this->messages = $messages;
    $this->files = $files;
    $this->members = $members;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmessages.messages'),
      $container->get('olmain.files'),
      $container->get('olmembers.members')
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
  public function buildForm(array $form, FormStateInterface $form_state, $op = null, $id = null) {

    // Defaults.
    $name = '';
    $body = '';
    $button_text = t('Submit');
    $hdd_file_location = $this->files->buildFileLocaton('message');
    $mail_send_default = array('1');
    $num_users = $this->members->countMembers(null, true);
    $send_mail_title = array( '1' => t('Notify other group members') .' ('.$num_users .')',);

    // Handle edit vars.
    if ($op == 'edit'){
      $message_data = $this->getMessageData($id);
      $name = $message_data->name;
      $body = $message_data->body;
      $button_text = t('Update Message');
      $mail_send_default = array('0');
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
      '#required' => true,
      '#attributes' => array('placeholder' => t('Add a title...'), 'class' => array('form-control'), 'maxlength' => '150'),
      '#suffix' => '</div>'
    ];
    $form['body'] = [
      '#prefix' => '<div class="form-group message-body">',
      '#type' => 'text_format',
      '#format' => 'ol_rich_text',
      '#weight' => '20',
      '#default_value' => $body,
      '#required' => true,
      '#suffix' => '</div>'
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="row"><div class="col-12 col-md-6"><div class="form-group file-upload-wrapper">',
      '#allowed_tags' => ['div'],
      '#weight' => '25',
    ];

    $form['files'] = array(
      '#title' => t('Attach files'),
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#upload_location' => 'private://'.$hdd_file_location,
      '#multiple' => TRUE,
      '#upload_validators' => array(
        'file_validate_extensions' => $this->files->getAllowedFileExtentions(),
      ),
      '#weight' => '30',
    );
    $form['markup_2'] = [
      '#type' => 'markup',
      '#markup' => '</div></div>',
      '#allowed_tags' => ['div'],
      '#weight' => '35',
    ];
    $form['send_mail'] = array(
      '#prefix' => '<div class="col-12 col-md-6"><div class="form-group send_mail_checkbox">',
      '#title' => t('Email Notifications'),
      '#type' => 'checkboxes',
      '#options' => $send_mail_title,
      '#default_value' => $mail_send_default,
      '#attributes' => array(
        'data-toggle' => 'toggle',
        'data-onstyle' => 'success',
        'data-size' => 'xs',
      ),
      '#weight' => '40',
      '#suffix' => '</div></div></div>'
    );
    $form['submit'] = [
      '#prefix' => '</div><div class="modal-footer">',
      '#type' => 'submit',
      '#weight' => '100',
      '#attributes' => array('class' => array('btn btn-success')),
      '#value' => $button_text,
      '#suffix' => '</div>'
    ];

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
    $body = $form_state->getValue('body')['value'];
    $body = check_markup($body,'ol_rich_text');
    $send_mail = $form_state->getValue('send_mail')[1];
    $files = $form_state->getValue('files');
    // Existing, update message.
    if(is_numeric($id)){
      $this->messages->updateMessage($id, $name, $body, $send_mail);
    }
    // New, save message.
    elseif(empty($id)){
      $id = $this->messages->saveMessage($name, $body, $send_mail);
    }
    if(!empty($files)) {
      $this->files->saveFiles($files, 'message', $id);
    }
  }

  /**
   * @param $id
   * @return mixed
   */
  private function getMessageData($id){
    $query = \Drupal::database()->select('ol_message', 'mess');
    $query->addField('mess', 'body');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $id);
    return $query->execute()->fetchObject();
  }

}


