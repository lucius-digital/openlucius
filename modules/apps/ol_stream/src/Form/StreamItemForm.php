<?php

namespace Drupal\ol_stream\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_stream\Services\OlStream;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class StreamItemForm.
 */
class StreamItemForm extends FormBase {

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $language_manager
   */
  protected $groups;

  /**
   * @var $language_manager
   */
  protected $stream;

  /**
   * Class constructor.
   * @param AccountInterface $account
   */
  public function __construct(OlMembers $members, Messenger $messenger, OlGroups $groups, OlStream $stream) {
    $this->members = $members;
    $this->messenger = $messenger;
    $this->groups = $groups;
    $this->stream = $stream;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmembers.members'),
      $container->get('messenger'),
      $container->get('olmain.groups'),
      $container->get('olstream.stream')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stream_item_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_server = null) {

    $disabled = (empty($node_server));
    $placeholder_text = (empty($node_server)) ? t('Disabled, because Node.js server is not set.') : t('Your chat-message...');

    $form['message'] = [
      '#prefix' => '<div class="container-md">
                            <div class="row">
                                <div class="col-md-11">
                                    <div class="row">
                                        <div class="col-12 chat-col-12">
                                            <div class="input-group flex-nowrap mb-3 mb-lg-0">',
      '#type' => 'textfield',
      '#required' => true,
      '#disabled' => $disabled,
      '#attributes' => array('class' => array('form-control'), 'placeholder' => $placeholder_text, 'maxlength' => 4000),
    ];
    $form['actions'] = [
      '#prefix' => '<div class="input-group-append">',
      '#type' => 'button',
      '#disabled' => $disabled,
      '#value' => t('Send',array(), array('context' => 'stream_message')),
      '#attributes' => array('class' => array('btn btn-outline-secondary')),
      '#suffix' => '</div></div></div></div></div></div></div>',
      '#ajax' => [
        'callback' => '::submitStreamAjax',
        'event' => 'click',
        'disable-refocus' => true,
        'progress' => [
          'type' => 'none',
        ]
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Ajax callback to validate the email field.
   */
  public function submitStreamAjax(array &$form, FormStateInterface $form_state) {

    // Initiate.
    $response = new AjaxResponse();
    $message = trim(Xss::filter($form_state->getValue('message')));
    $group_id = $this->groups->getCurrentGroupId();
    $user_picture = $this->members->getUserPictureUrl();
    $created = date('H:i' ); // Needed for appending and emitting message.
    $timestamp = time(); // Needed to help determine if screen has to refresh due to missed messages.

    if(strlen($message) > 0){
      $this->stream->addStreamItem($group_id, 'chat_added', $message, 'chat', 0); // Create stream item.
      $response->addCommand(new InvokeCommand('#edit-message', 'val', ['']));  // Empty message field.
      // Post message via post_message, that will handle appending and emitting to other users via socket.io.
      $response->addCommand(new InvokeCommand(NULL, 'post_message',
                                              [$group_id, $message, $user_picture, $created, $timestamp]));
    }
    // Wipe all messages, so on page refresh nothing comes up.
    $this->messenger->deleteAll();
    // Return response.
    return $response;

  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We need this function, because interface requires it.
    // But nothing is needed here, it's all ajax above.
  }

}
