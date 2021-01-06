<?php

namespace Drupal\ol_chat\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_chat\Services\OlChat;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class ChatItemForm.
 */
class ChatItemForm extends FormBase {

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $language_manager
   */
  protected $groups;

  /**
   * @var $chat
   */
  protected $chat;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $route
   */
  protected $route;

  /**
   * Class constructor.
   *
   * @param \Drupal\ol_members\Services\OlMembers $members
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\ol_main\Services\OlGroups $groups
   * @param \Drupal\ol_chat\Services\OlChat $chat
   * @param \Drupal\ol_main\Services\OlFiles $files
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   */
  public function __construct(OlMembers $members, Messenger $messenger, OlGroups $groups, OlChat $chat, OlFiles $files,  CurrentRouteMatch $route) {
    $this->members = $members;
    $this->messenger = $messenger;
    $this->groups = $groups;
    $this->chat = $chat;
    $this->files = $files;
    $this->route = $route;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmembers.members'),
      $container->get('messenger'),
      $container->get('olmain.groups'),
      $container->get('olchat.chat'),
      $container->get('olmain.files'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chat_item_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_server = null) {

    $disabled = (empty($node_server));
    $placeholder_text = (empty($node_server)) ? t('Disabled, because Node.js server is not set.')
                        : t('Chat-message.. (@.. to notify)');

    $form['message'] = [
      '#prefix' => '<div class="row mt-3 ml-2">
                      <div class="col-md-8">
                          <div class="input-group flex-nowrap mb-0 mb-lg-0">',
      '#type' => 'textfield',
      '#disabled' => $disabled,
      '#attributes' => [
        'class' => ['form-control'],
        'placeholder' => $placeholder_text,
        'maxlength' => 4000,
        'autocomplete' => 'off',
      ],
    ];
    $form['actions'] = [
      '#prefix' => '<div class="input-group-append">',
      '#type' => 'button',
      '#disabled' => $disabled,
      '#value' => t('Send',[], ['context' => 'chat_message']),
      '#attributes' => [
        'class' => ['btn btn-outline-secondary']
      ],
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::submitChatAjax',
        'event' => 'click',
        'disable-refocus' => true,
        'progress' => [
          'type' => 'none',
        ]
      ],
    ];
    $form['markup_form_row_start'] = [
      '#type' => 'markup',
      '#markup' => '<span data-toggle="tooltip" data-placement="top" title="Upload Files" data-delay="0">
                      <a class="btn btn-success btn-sm text-white ml-3 p-2 px-4" data-toggle="modal" data-target="#chatFilesModal">
                        <i class="lni lni-upload"></i>
                      </a>
                    </span>
                        </div></div></div>',
      '#allowed_tags' => ['a','i', 'div','span'],
    ];
    return $form;
  }

  /**
   * Ajax callback to validate the email field.
   */
  public function submitChatAjax(array &$form, FormStateInterface $form_state) {

    // Initiate.
    // To do optimize: prevent submit if no text was added, with jquery for example.
    $response = new AjaxResponse();
    $message = trim(Html::escape($form_state->getValue('message')));

    // Return asap if no message was given.
    if(strlen($message) <= 0){
      return $response;
    }

    // Get data.
    $group_id = $this->groups->getCurrentGroupId();
    $user_picture = $this->members->getUserPictureUrl();
    $created = date('H:i' );
    $timestamp = time();
    // Save new chat item.
    $this->chat->addChatItem($group_id, 'chat_added', $message, 'chat', 0);
    // Empty message field.
    $response->addCommand(new InvokeCommand('#edit-message', 'val', ['']));
    // Post message via js method post_message, that will handle appending and emitting to other users via socket.io.
    $response->addCommand(new InvokeCommand(NULL, 'post_message',
                                            [$group_id, $message, $user_picture, $created, $timestamp]));
    // Handle mentions
    if (strpos($message, '@') !== false) {
      $this->sendMentions($message);
    }

    // Wipe all messages, so on page refresh nothing comes up.
    $this->messenger->deleteAll();
    // Return response.
    return $response;

  }

  /**
   * @param $message
   */
  private function sendMentions($message) {
    // Get users in group.
    // To do optimize: get an array of group users and use in_array: no loop needed.
    $group_users = $this->members->getUsersInGroup();
    // Loop though all users in group and send email if match.
    foreach($group_users as $group_user){
      if (strpos($message, $group_user->name) !== false) {
        $email = $group_user->mail;
        $this->sendNotifications($email, $message);
      }
    }
  }

  /**
   * @param $email
   * @param $message
   */
  private function sendNotifications($email, $message){
    // Build mail vars.
    $emails = [$email];
    $mail = \Drupal::service('olmain.mail');
    $uid = \Drupal::currentUser()->id();
    $sender = User::load($uid)->getAccountName();
    $gid = $this->route->getParameter('gid');
    $url = Url::fromRoute('ol_chat.group', ['gid' => $gid])->toString();
    $cta_text = t('View Chat');
    $mail_body = t('@user notified you in this chat message:', ['@user' => $sender]);
    $subject = t('Mention from @username', ['@username' => $sender]);
    // Send mails via service.
    $mail->sendMail($subject, $url, $mail_body, $emails, null, null, $cta_text, null, $message);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We need this function, because interface requires it.
  }

}
