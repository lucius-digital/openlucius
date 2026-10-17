<?php

namespace Drupal\ol_messages\Services;

use Drupal\Core\Url;
use Drupal\ol_message\Entity\OlMessage;

/**
 * Service for managing messages in the ol_messages module.
 */
class OlMessages {

  /**
   * Current route match.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGenerator
   */
  protected $route;

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Stream service.
   *
   * @var \Drupal\ol_stream\Services\OlStream
   */
  protected $stream;

  /**
   * Mail service.
   *
   * @var \Drupal\ol_mail\Services\OlMail
   */
  protected $mail;

  /**
   * Groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * Comments service.
   *
   * @var \Drupal\ol_main\Services\OlComments
   */
  protected $comments;

  /**
   * Files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * Notifications service.
   *
   * @var \Drupal\ol_notifications\Services\OlNotifications
   */
  protected $notifications;

  /**
   * OlMessages constructor.
   *
   * @param \Symfony\Component\Routing\Generator\UrlGenerator $route
   *   Current route match.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\ol_stream\Services\OlStream $stream
   *   Stream service.
   * @param \Drupal\ol_mail\Services\OlMail $mail
   *   Mail service.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   Groups service.
   * @param \Drupal\ol_main\Services\OlComments $comments
   *   Comments service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   Files service.
   * @param \Drupal\ol_notifications\Services\OlNotifications $notifications
   *   Notifications service.
   */
  public function __construct($route, $members, $stream, $mail, $groups, $comments, $files, $notifications) {
    $this->route = $route;
    $this->members = $members;
    $this->stream = $stream;
    $this->mail = $mail;
    $this->groups = $groups;
    $this->comments = $comments;
    $this->files = $files;
    $this->notifications = $notifications;
  }

  /**
   * Render a list of messages.
   *
   * @param array $message_list_data
   *   Array of message data objects.
   * @param string $view
   *   View type: 'list' or 'page'.
   *
   * @return string
   *   Rendered HTML string of messages.
   *
   * @throws \Exception
   */
  public function renderMessagesList($message_list_data, $view = 'list') {

    // Initiate html.
    $messages_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($message_list_data as $message) {
      $message_data = $this->getMessageData($message->id);
      $messages_row_data['body'] = htmlspecialchars_decode($message_data->body);
      $messages_row_data['name'] = $message_data->name;
      $messages_row_data['created'] = ($view == 'list')
        ? time_elapsed_string('@' . $message_data->created)
        : $message_data->created;
      $messages_row_data['username'] = $message_data->username;
      $messages_row_data['id'] = $message_data->id;
      $messages_row_data['id_group'] = $message_data->group_id;
      $messages_row_data['user_id'] = $message_data->user_id;
      $messages_row_data['owner'] = $message_data->user_id == $this->members->getUserId();
      $messages_row_data['view'] = $view;
      $messages_row_data['user_picture'] = $this->members->getUserPictureUrl($message_data->user_id);
      $messages_row_data['link'] = '/group/' . $message_data->group_id . '/messages/' . $message_data->id;
      if ($messages_row_data['owner'] == TRUE) {
        $messages_row_data['message_edit_form'] = \Drupal::formBuilder()->getForm(
          '\Drupal\ol_messages\Form\MessageForm', 'edit', $message_data->id
        );
      }
      $messages_row_data['like_button'] = \Drupal::formBuilder()->getForm(
        '\Drupal\ol_like\Form\LikeForm', 'message', $message_data->id
      );
      $messages_row_data['comment_count'] = $this->comments->getCommentCount($message_data->id, 'message', $message_data->group_id);
      if ($view == 'page') {
        $messages_row_data['files'] = $this->files->getAttachedFiles('message', $message_data->id);
      }
      // Different template based on list or detail page.
      $template = ($view == 'list') ? 'message_card_list' : 'message_card';
      $render = ['#theme' => $template, '#vars' => $messages_row_data];
      $messages_html .= \Drupal::service('renderer')->render($render);
    }
    return $messages_html;
  }

  /**
   * Get a list of messages.
   *
   * @param int|null $message_id
   *   Optional message ID to filter by.
   * @param int|null $num_per_page
   *   Number of results per page.
   * @param int|null $offset
   *   Offset for pagination.
   * @param bool $get_total
   *   If TRUE, return total count instead of results.
   *
   * @return mixed
   *   Array of message objects or total count.
   */
  public function getMessagesList($message_id = NULL, $num_per_page = NULL, $offset = NULL, $get_total = FALSE) {
    // Get current group id.
    $gid = $this->groups->getCurrentGroupId();
    // Get message data.
    $query = \Drupal::database()->select('ol_message', 'mess');
    $query->addField('mess', 'id');
    $query->condition('mess.group_id', $gid);
    if (!empty($message_id)) {
      $query->condition('mess.id', $message_id);
    }
    $query->condition('mess.status', 1);
    $query->orderBy('mess.created', 'desc');
    // Data for message list.
    if ($get_total == FALSE) {
      $query->range($offset, $num_per_page);
      $message_data = $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == TRUE) {
      $message_data = $query->countQuery()->execute()->fetchField();
    }
    return $message_data;
  }

  /**
   * Retrieve detailed message data by ID.
   *
   * @param int $id
   *   The message ID.
   *
   * @return object
   *   The message data object.
   */
  private function getMessageData($id) {
    // Get message detail data.
    $query = \Drupal::database()->select('ol_message', 'mess');
    $query->addField('mess', 'body');
    $query->addField('mess', 'group_id');
    $query->addField('mess', 'id');
    $query->addField('mess', 'name');
    $query->addField('mess', 'created');
    $query->addField('mess', 'user_id');
    $query->addField('user', 'name', 'username');
    $query->condition('mess.id', $id);
    $query->join('users_field_data', 'user', 'user.uid = mess.user_id');
    $message_data = $query->execute()->fetchObject();
    return $message_data;
  }

  /**
   * Get the title of a message.
   *
   * @param array $message_data
   *   The message data array.
   *
   * @return string
   *   The message title.
   */
  public function getMessageTitle($message_data) {
    $query = \Drupal::database()->select('ol_message', 'mess');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $message_data[0]->id);
    return $query->execute()->fetchField();
  }

  /**
   * Save a new message.
   *
   * @param string $name
   *   The message title.
   * @param string $body
   *   The message body content.
   *
   * @param bool $send_mail
   *   Whether to send email notifications.
   *
   * @return int|string|null
   *   The new message ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveMessage($name, $body, $send_mail = FALSE) {
    // Get group id.
    $gid = $this->route->getParameter('gid');
    // Save new message.
    $message = OlMessage::create([
      'name' => $name,
      'body' => $body,
      'group_id' => $gid,
    ]);
    $message->save();
    $id = $message->id();
    // Url for mails.
    $url = Url::fromRoute('lus_message.message', ['gid' => $gid, 'id' => $id])->toString();
    // Send @-mentions.
    $this->notifications->sendMentions($body, $url);
    // Add stream item.
    $this->stream->addStreamItem($gid, 'message_added', $name, 'messages', $id);
    // Mail if true.
    if ($send_mail == TRUE) {
      // Send mails.
      $intro_text = t('A new message was posted:');
      $this->mail->sendMail(
        $name, $url, $intro_text, NULL, NULL, NULL, t('Find out more'), NULL,
        strip_tags(shortenString($body, 100))
      );
    }
    // Message.
    \Drupal::messenger()->addStatus(t('Your message was added successfully.'));
    // Return id.
    return $id;
  }

  /**
   * Update an existing message.
   *
   * @param int $id
   *   The message ID.
   * @param string $name
   *   The updated message title.
   * @param string $body
   *   The updated message body content.
   *
   * @param bool $send_mail
   *   Whether to send email notifications.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateMessage($id, $name, $body, $send_mail = FALSE) {
    // Update message with spoofing protection.
    if ($this->isMessageOwner($id)) {
      $entity = OlMessage::load($id);
      $entity->set("name", $name);
      $entity->set("body", $body);
      $entity->save();
      // Url for mailing.
      $gid = $this->route->getParameter('gid');
      $url = Url::fromRoute('lus_message.message', ['gid' => $gid, 'id' => $id])->toString();
      // Send @-mentions.
      $this->notifications->sendMentions($body);
      // Mail if checked by user.
      if ($send_mail == TRUE) {
        $this->mail->sendMail($name, $url);
      }
      // Add message.
      \Drupal::messenger()->addStatus(t('Your message was updated successfully.'));
    }
  }

  /**
   * Check if the current user is the owner of the message.
   *
   * @param int $id
   *   The message ID.
   *
   * @return bool
   *   TRUE if the current user owns the message.
   */
  private function isMessageOwner($id) {
    $query = \Drupal::database()->select('ol_message', 'olm');
    $query->addField('olm', 'user_id');
    $query->condition('olm.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }

}
