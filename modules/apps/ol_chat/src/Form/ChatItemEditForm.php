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
 * Class ChatItemEditForm.
 */
class ChatItemEditForm extends FormBase {

  /**
   * @var $chat
   */
  protected $chat;

  /**
   * @param \Drupal\ol_main\Services\OlComments $comments
   */
  public function __construct( OlChat $chat) {
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
    return 'chat_item_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build form.
    $form['markup_form_row_start'] = [
      '#type' => 'markup',
      '#markup' => '<div class="modal-body pb-3"><div class="form-row pt-3 pb-2" id="edit-description-row">',
      '#allowed_tags' => ['div'],
    ];
    $form['chat_item_id'] = [
      '#type' => 'hidden',
      '#weight' => '0',
      '#attributes' => [
        'id' => 'edit-chat-id',
      ],
    ];
    $form['chat_body'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'class' => array('form-control'),
        'id' => 'edit-chat-body',
        'maxlength' => 4000,
      ],
    ];
    $form['save_chat'] = [
      '#prefix' => '</div></div><div class="modal-footer">',
      '#type' => 'button',
      '#value' => t('Save'),
      '#attributes' => [
        'data-disable-refocus' => 'true',
        'class' => array('btn btn-success'),
        'onsubmit' => 'return false',
      ],
      '#ajax' => [
        'callback' => '::saveChatItem',
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Needed for interface.
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveChatItem(array &$form, FormStateInterface $form_state) {
    // Init.
    $response = new AjaxResponse();
    // Get data.
    $chat_id = Html::escape($form_state->getValue('chat_item_id'));
    $body = Html::escape($form_state->getValue('chat_body'));

    // Update chat item.
    if (is_numeric($chat_id)) {
      // Save to database.
      $this->chat->updateChatItem($chat_id, $body);
      // Clear form body and id.
      $response->addCommand(new InvokeCommand('#edit-chat-body', 'val', ['']));
      $response->addCommand(new InvokeCommand('#edit-chat-id', 'val', ['']));
      // Place new comment in UI.
      $response->addCommand(new InvokeCommand('#chat-body-id_'.$chat_id .' .body-text', 'text', [htmlspecialchars_decode($body)]));
      // Close modal.
      $response->addCommand(new InvokeCommand('#editChatItemModal','modal',['hide']));
      // Update last message timestamp.
      $response->addCommand(new InvokeCommand('#last_message_timestamp','text',[time()]));

      // Clear messages and return response.
      \Drupal::messenger()->deleteAll();
      return $response;
    }
  }
}
