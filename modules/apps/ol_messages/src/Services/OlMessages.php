<?php

namespace Drupal\ol_messages\Services;

use Drupal\Core\Url;
use Drupal\ol_message\Entity\OlMessage;

/**
 * Class OlMessages.
 */
class OlMessages{

  /**
   * @var $route
   */
  protected $route;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $stream
   */
  protected $stream;

  /**
   * @var $mail
   */
  protected $mail;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $comments
   */
  protected $comments;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @param $route
   * @param $members
   * @param $stream
   * @param $mail
   */
  public function __construct($route, $members, $stream, $mail, $groups, $comments, $files) {
    $this->route = $route;
    $this->members = $members;
    $this->stream = $stream;
    $this->mail = $mail;
    $this->groups = $groups;
    $this->comments = $comments;
    $this->files = $files;
  }
  /**
   * @param $message_list_data
   * @param string $view
   *
   * @return string
   * @throws \Exception
   */
  public function renderMessagesList($message_list_data, $view = 'list'){

    // Initiate html.
    $messages_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($message_list_data as $message){
      $message_data = $this->getMessageData($message->id);
      $messages_row_data['body'] = $message_data->body;
      $messages_row_data['name'] = $message_data->name;
      $messages_row_data['created'] = ($view == 'list') ? time_elapsed_string('@'.$message_data->created): $message_data->created;
      $messages_row_data['username'] = $message_data->username;
      $messages_row_data['id'] = $message_data->id;
      $messages_row_data['id_group'] = $message_data->group_id;
      $messages_row_data['user_id'] = $message_data->user_id;
      $messages_row_data['owner'] = $message_data->user_id == $this->members->getUserId();
      $messages_row_data['view'] = $view;
      $messages_row_data['user_picture'] = $this->members->getUserPictureUrl($message_data->user_id);
      $messages_row_data['link'] = '/group/'.$message_data->group_id.'/messages/'.$message_data->id;
      if($messages_row_data['owner'] == TRUE) {
        $messages_row_data['message_edit_form'] = \Drupal::formBuilder()->getForm(\Drupal\ol_messages\Form\MessageForm::class, 'edit', $message_data->id);
      }
      $messages_row_data['like_button'] = \Drupal::formBuilder()->getForm(\Drupal\ol_like\Form\LikeForm::class, 'message', $message_data->id);
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
   * @param null $message_id
   *
   * @param null $num_per_page
   * @param null $offset
   * @param bool $get_total
   *
   * @return mixed
   */
  public function getMessagesList($message_id = null, $num_per_page = null, $offset = null , $get_total = false){
    // Get current group id.
    $gid = $this->groups->getCurrentGroupId();
    // Get message data.
    $query = \Drupal::database()->select('ol_message', 'mess');
    $query->addField('mess', 'id');
    $query->condition('mess.group_id', $gid);
    if(!empty($message_id)) {
      $query->condition('mess.id', $message_id);
    }
    $query->condition('mess.status', 1);
    $query->orderBy('mess.created', 'desc');
    // Data for message list.
    if ($get_total == false) {
      $query->range($offset, $num_per_page);
      $message_data = $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == true) {
      $message_data = $query->countQuery()->execute()->fetchField();
    }
    return $message_data;
  }

  /**
   * @param $id
   * @return mixed
   */
  private function getMessageData($id){
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
   * @param $message_data
   *
   * @return mixed
   */
  public function getMessageTitle($message_data){
    $query = \Drupal::database()->select('ol_message', 'mess');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $message_data[0]->id);
    return $query->execute()->fetchField();
  }

  /**
   * @param $name
   * @param $body
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveMessage($name, $body, $send_mail = false){
    // Get group id.
    $gid = $this->route->getParameter('gid');
    // Save new message.
    $message = OlMessage::create([
      'name' => $name,
      'body' =>  $body,
      'group_id' => $gid
    ]);
    $message->save();
    $id = $message->id();
    // Add stream item.
    $stream_body = t('Added a message: @message', array('@message' => $name)); // Create new stream item.
    $this->stream->addStreamItem($gid, 'message_added', $stream_body, 'message', $id); // Create stream item.
    // Mail if true
    if($send_mail == true){
      // Generate url and send mails.
      $url = Url::fromRoute('lus_message.message', ['gid' => $gid, 'id' => $id], ['absolute' => TRUE])->toString();
      $this->mail->sendMail($name, $url);
    }
    // Message.
    \Drupal::messenger()->addStatus(t('Your message was added successfully.'));
    // Return id
    return $id;
  }

  /**
   * @param $id
   * @param $name
   * @param $body
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateMessage($id, $name, $body, $send_mail = false){
    // Update message  with spoofing protection.
    if($this->isMessageOwner($id)) {
      $entity = OlMessage::load($id);
      $entity->set("name", $name);
      $entity->set("body", $body);
      $entity->save();
      // Mail if checked by user.
      if($send_mail == true){
        // Generate url and send mails.
        $gid = $this->route->getParameter('gid');
        $url = Url::fromRoute('lus_message.message', ['gid' => $gid, 'id' => $id], ['absolute' => TRUE])->toString();
        $this->mail->sendMail($name, $url);
      }
      // Add message.
      \Drupal::messenger()->addStatus(t('Your message was updated successfully.'));
    }
  }


  /**
   * @param $id
   * @return bool
   */
  private function isMessageOwner($id){
    $query = \Drupal::database()->select('ol_message', 'olm');
    $query->addField('olm', 'user_id');
    $query->condition('olm.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }



}
