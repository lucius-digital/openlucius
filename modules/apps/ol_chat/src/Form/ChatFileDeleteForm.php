<?php

namespace Drupal\ol_chat\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ol_chat\Services\OlChat;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class ChatFileDeleteForm.
 */
class ChatFileDeleteForm extends FormBase {

  /**
   * @var $chat
   */
  protected $chat;


  /**
   * @param \Drupal\ol_main\Services\OlComments $comments
   */
  public function __construct(OlChat $chat) {
    $this->chat = $chat;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olchat.chat')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build form.
    $form['markup_form_row_start'] = [
      '#type' => 'markup',
      '#markup' => '<div class="modal-body pb-3"><div class="form-row pb-2" id="edit-description-row">',
      '#allowed_tags' => ['div'],
    ];
    $form['chat_item_id'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => [
        'id' => 'file-remove-chat-id',
      ],
    ];
    $form['markup_body'] = [
      '#type' => 'markup',
      '#markup' => '<div class="pb-2"><i class="lni lni-warning"></i> '.t('The following files will be deleted, are you sure?') .'</div>
                        <div class="text-muted small pl-4" id="modal-remove-message"></div>',
      '#allowed_tags' => ['div','i'],
    ];
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '</div></div><div class="modal-footer"><button type="button" class="btn btn-light text-muted" data-dismiss="modal">' .t('Cancel').'</button>',
      '#allowed_tags' => ['button','div'],
    ];
    $form['remove_file'] = [
      '#type' => 'button',
      '#value' => t('Yes, Delete Permanently'),
      '#attributes' => [
        'data-disable-refocus' => 'true',
        'class' => array('btn btn-danger'),
        'onsubmit' => 'return false',
      ],
      '#ajax' => [
        'callback' => '::deleteChatFile',
        'event' => 'click',
        'effect' => 'fade',
        'progress' => [
          'type' => 'bar',
          'message' => t('Please wait...'),
        ],
      ],
      '#suffix' => '</div></div>'
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteChatFile(array &$form, FormStateInterface $form_state) {
    // Init.
    $response = new AjaxResponse();
    // Get data.
    $chat_id = Html::escape($form_state->getValue('chat_item_id'));
    // Delete files and update chat item.
    if (is_numeric($chat_id)) {
      // Save to database.
      $this->chat->deleteChatItemFiles($chat_id);
      // Clear form body and id.
      $response->addCommand(new InvokeCommand('#modal-remove-message', 'val', ['']));
      $response->addCommand(new InvokeCommand('#file-remove-chat-id', 'val', ['']));
      // Place new comment in UI.
      $response->addCommand(new InvokeCommand('#files-body-id_'.$chat_id, 'text', [t('-files removed-')]));
      // Close modal.
      $response->addCommand(new InvokeCommand('#deleteFileModal','modal',['hide']));
      // Clear messages and return response.
      \Drupal::messenger()->deleteAll();
      return $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
