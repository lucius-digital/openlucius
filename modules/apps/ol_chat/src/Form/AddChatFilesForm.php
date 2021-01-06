<?php

namespace Drupal\ol_chat\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\ol_board\Services\OlTasks;
use Drupal\ol_chat\Services\OlChat;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Stack\Append;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class AddChatFilesForm.
 */
class AddChatFilesForm extends FormBase {

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $chat
   */
  protected $chat;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $language_manager
   */
  protected $groups;

  /**
   * Class constructor.
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct(OlFiles $files, OlChat $chat, OlMembers $members, OlGroups $groups) {
    $this->files = $files;
    $this->chat = $chat;
    $this->members = $members;
    $this->groups = $groups;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmain.files'),
      $container->get('olchat.chat'),
      $container->get('olmembers.members'),
      $container->get('olmain.groups')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_chat_file_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build hdd file location.
    $hdd_file_location = $this->files->buildFileLocaton('chat');
    // Build form.
    $form['markup_form_row_start'] = [
      '#type' => 'markup',
      '#markup' => '<div class="modal-body pb-3"><div class="form-row pt-3 pb-2" id="edit-description-row">',
      '#allowed_tags' => ['div'],
    ];
    $form['files'] = array(
      '#type' => 'managed_file',
      '#required' => FALSE,
      '#upload_location' => 'private://'.$hdd_file_location,
      '#multiple' => TRUE,
      '#upload_validators' => array(
        'file_validate_extensions' => $this->files->getAllowedFileExtentions(),
      ),
    );
    $form['task_id'] = [
      '#type' => 'hidden',
      '#default_value' => '',
      '#attributes' => [
        'id' => 'task_id_files',
      ],
    ];
    $form['upload_files'] = [
      '#prefix' => '</div></div><div class="modal-footer">',
      '#type' => 'button',
      '#value' => t('Add Files to Chat'),
      '#attributes' => [
        'data-disable-refocus' => 'true',
        'class' => array('btn btn-success'),
        'onsubmit' => 'return false',
      ],
      '#ajax' => [
        'callback' => '::callbackSaveFiles',
        'event' => 'click',
        'effect' => 'fade',
        'progress' => [
          'type' => 'bar',
          'message' => t('Please wait...'),
        ],
      ],
      '#suffix' => '</div></div>'
    ];
    // End class="form-row"
    $form['markup_form_row_end'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
      '#allowed_tags' => ['div'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Interface requirement, dummy method.
  }

  /**
   * {@inheritdoc}
   */
  public function callbackSaveFiles(array &$form, FormStateInterface $form_state) {

    // Init response.
    $response = new AjaxResponse();
    // Get form data and save files.
    $fids = $form_state->getValue('files')['fids'];

    // Return if no files where uploaded.
    if(empty($fids)) {
      return $response;
    }

    // Save files.
    $this->files->saveFiles($fids, 'chat');
    // Save chat item.
    $this->chat->addChatItem(null, 'FILE', 'FILE', 'files_added', json_encode($fids));

    // Clean upload form.
    // Remove file ids from form.
    $response->addCommand(new InvokeCommand('#add-chat-file-form [name$="files[fids]"]','val',['']));
    // Remove temp/saved files from form.
    $response->addCommand(new RemoveCommand('#add-chat-file-form .form-managed-file .js-form-item'));
    // Remove remove button.
    $response->addCommand(new RemoveCommand('#add-chat-file-form [data-drupal-selector="edit-files-remove-button"]'));
    // Hide upload button.
    $response->addCommand(new InvokeCommand('#add-chat-file-form [data-drupal-selector="edit-files-upload-button"]','addClass',['hidden']));

    // Append and socket.emit new chat file item.
    // Files html.
    $vars['files'] = $this->chat->getFileLinks(json_encode($fids));
    $render = ['#theme' => 'chat_file_item', '#vars' => $vars];
    $files_html = \Drupal::service('renderer')->render($render);
    $message = t('Uploaded: ') .$files_html;
    // Extra needed vars.
    $group_id = $this->groups->getCurrentGroupId();
    $user_picture = $this->members->getUserPictureUrl();
    $created = date('H:i' );
    $timestamp = time();
    // Append and emit.
    $response->addCommand(new InvokeCommand(NULL, 'post_message',
                          [$group_id, $message, $user_picture, $created, $timestamp]));
    // Close modal.
    $response->addCommand(new InvokeCommand('#chatFilesModal','modal',['hide']));

    // Clear messages and return response.
    \Drupal::messenger()->deleteAll();
    return $response;
  }

}


