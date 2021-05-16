<?php

namespace Drupal\ol_chat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_chat\Services\OlChat;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ol_main\Services\OlGroups;

/**
 * Class ChatController.
 */
class ChatController extends ControllerBase {

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $form_builder
   */
  protected $form_builder;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $chat
   */
  protected $chat;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlMembers $members, FormBuilder $form_builder, OlGroups $groups, OlChat $chat) {
    $this->members = $members;
    $this->form_builder = $form_builder;
    $this->groups = $groups;
    $this->chat = $chat;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olmembers.members'),
      $container->get('form_builder'),
      $container->get('olmain.groups'),
      $container->get('olchat.chat')
    );
  }

  public function getChat($gid){
    // Group uuid is used in Javascript, for socket.io room id and ajax calls.
    // This is for security hardening.
    $group_uuid = $this->groups->getGroupUuidById($gid);
    $username = $this->members->getUserName();
    $user_picture = $this->members->getUserPictureUrl();
    $group_users = $this->members->getUsersInGroup(); // Used for mentions.
    $node_server = \Drupal::config('ol_main.admin_settings')->get('nodejs_server_url');
    // Get forms.
    $chat_form = $this->form_builder->getForm(\Drupal\ol_chat\Form\ChatItemForm::class, $node_server);
    $load_more = $this->form_builder->getForm(\Drupal\ol_chat\Form\LoadPreviousChatItemsForm::class);
    $files_form = $this->form_builder->getForm(\Drupal\ol_chat\Form\AddChatFilesForm::class);
    $edit_chat_form = $this->form_builder->getForm(\Drupal\ol_chat\Form\ChatItemEditForm::class);
    $file_delete_form = $this->form_builder->getForm(\Drupal\ol_chat\Form\ChatFileDeleteForm::class);
    // This is needed to determine refreshing (via javascript).
    $last_message_timestamp = $this->chat->getLastMessageTimestamp($group_uuid);
    // Build it.
    $theme_vars = [
      'chat_form' => $chat_form,
      'files_form' => $files_form,
      'edit_chat_form' => $edit_chat_form,
      'file_delete_form' => $file_delete_form,
      'load_more' => $load_more,
      'last_message_timestamp' => $last_message_timestamp,
      'node_server' => $node_server,
    ];
    return [
      '#theme' => 'chat_wrapper',
      '#attached' => [
        'library' => [
          'ol_chat/node_server', // Build dynamically in ol_chat_library_info_build().
          'ol_chat/chat',
          'ol_chat/mentions',
          'ol_files/ol_files',
        ],
        'drupalSettings' => [
          'group_uuid' => $group_uuid,
          'node_server' => $node_server,
          'username' => $username,
          'user_picture' => $user_picture,
          'users_in_group' => $group_users
        ],
      ],
        '#vars' => $theme_vars,
    ];
  }
}
