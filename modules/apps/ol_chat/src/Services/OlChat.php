<?php

namespace Drupal\ol_chat\Services;

use Drupal\Core\Url;
use Drupal\ol_chat_item\Entity\OlChatItem;

/**
 * Class OlChat.
 */
class OlChat{

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $renderer
   */
  protected $renderer;
  /**
   * @var $files
   */
  protected $files;

  /**
   * OlMembers constructor.
   *
   * @param $groups
   * @param $members
   * @param $renderer
   * @param $files
   */
  public function __construct($groups, $members, $renderer, $files) {
    $this->groups = $groups;
    $this->members = $members;
    $this->renderer = $renderer;
    $this->files = $files;
  }

  /**
   * @param $group_uuid
   * @param int $offset
   * @param int $length
   *
   * @return mixed
   */

  function getChatList($group_uuid, $offset = 0){
    // Get query length.
    $length = $this->getChatItemsQueryLength();
    // Get plain group_id from uuid.
    $group_id = $this->groups->getGroupIdByUuid($group_uuid);
    // Get message data.
    $query = \Drupal::database()->select('ol_chat_item', 'osi');
    $query->addField('osi', 'id');
    $query->addField('osi', 'created');
    $query->addField('osi', 'changed');
    $query->addField('osi', 'user_id');
    $query->addField('osi', 'chat_body');
    $query->addField('osi', 'group_id');
    $query->addField('osi', 'entity_type');
    $query->addField('osi', 'entity_id');
    $query->addField('ufd', 'name');
    $query->condition('osi.group_id', $group_id);
    $query->condition('osi.status', 1);
    $query->join('users_field_data', 'ufd', 'ufd.uid = osi.user_id');
    $query->orderBy('osi.id', 'desc');
    $query->range($offset, $length);
    return $query->execute()->fetchAll();
  }

  /**
   * @return int
   */
  public function getChatItemsQueryLength(){
    // Centralized, because needs to be exact the same in multiple functions.
    return 25;
  }

  /**
   * @param $chat_items
   * @param null $direction
   *
   * @return string
   */
  function renderChatList($chat_items, $direction = null){
    if ($direction != 'recent_on_top') {
      // Sort array, so newest chat items will be on bottom
      usort($chat_items, function ($a, $b) {
        return strcmp($a->created, $b->created);
      });
    }
    // Initiate vars.
    $chat_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($chat_items as $chat){
      $chat_row_data['files'] = null;
      $chat_row_data['is_files'] = false;
      $chat_row_data['id'] = $chat->id;
      $chat_row_data['created'] = (date('d-m-Y') == date('d-m-Y', $chat->created)) ? date('H:i', $chat->created) : date('D, d M Y, H:i', $chat->created);
      $chat_row_data['owner'] = $chat->user_id == $this->members->getUserId();
      $chat_row_data['edited'] = ($chat->created != $chat->changed);
      $chat_row_data['user_picture_url'] = $this->members->getUserPictureUrl($chat->user_id);
      $chat_row_data['user_name'] = $chat->name;
      $chat_row_data['chat_body'] = trim(detectAndCreateLink($chat->chat_body));
      if ($chat->entity_type == 'files_added'){
        $chat_row_data['files'] = $this->getFileLinks($chat->entity_id);
        $chat_row_data['is_files'] = true;
      }
      $render = ['#theme' => 'chat_item', '#vars' => $chat_row_data];
      $chat_html .= $this->renderer->render($render);
    }
    return $chat_html;
  }

  /**
   * @param $id
   * @param $body
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateChatItem($id, $body){
    // Update comment with security check.
    if($this->isChatItemOwner($id)) {
      // Load and save, update.
      $entity = OlChatItem::load($id);
      $entity->set("chat_body", $body);
      $entity->save();
    }
  }


  /**
   * @param $id
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteChatItemFiles($id){

    // Delete comment with security check.
    if($this->isChatItemOwner($id)) {
      // Get file ids to be deleted.
      $file_ids = json_decode($this->getChatItemFileIds($id));
      foreach ($file_ids as $fid) {
        // Remove ol_file entry and 'physical' file.
        $this->files->removeOlFileAndFile($fid);
        // Update chat item entity.
        $entity = OlChatItem::load($id);
        $entity->set("entity_id", '');
        $entity->save();
      }
    }
  }

  /**
   * @param $id
   *
   * @return mixed
   */
  function getChatItemFileIds($id){
    // Get chat item data.
    $query = \Drupal::database()->select('ol_chat_item', 'osi');
    $query->addField('osi', 'entity_id');
    $query->condition('osi.id', $id);
    return $query->execute()->fetchField();
  }

  /**
   * @param $cid
   *
   * @return bool
   */
  private function isChatItemOwner($cid){
    $query = \Drupal::database()->select('ol_chat_item', 'oci');
    $query->addField('oci', 'user_id');
    $query->condition('oci.id', $cid);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }
  /**
   * @param $file_ids_json
   *
   * @return array
   */
  public function getFileLinks($file_ids_json){
    $file_ids = json_decode($file_ids_json, true);
    $files = array();
    foreach ($file_ids as $file_id) {
      $files[$file_id]['file_name'] = $this->files->getFileName($file_id);
      $file_uri = $this->files->getFileUri($file_id);
      $files[$file_id]['file_path'] = Url::fromUri(file_create_url($file_uri));
    }
    return $files;

  }

  /**
   * @param $uuid
   *
   * @return int|string
   */
  function getLastMessageTimestamp($uuid){
    // Get internal group_id from uuid.
    $group_id = $this->groups->getGroupIdByUuid($uuid);
    // Query for me, b*tch :)
    $query = \Drupal::database()->select('ol_chat_item', 'osi');
    $query->addField('osi', 'created');
    $query->condition('osi.group_id', $group_id);
    $query->condition('osi.status', 1);
    $query->orderBy('osi.created', 'desc');
    $query->range(0, 1);
    $timestamp = $query->execute()->fetchField();
    // Return empty string if no result, to prevent error on empty chats.
    return (is_numeric($timestamp)) ? $timestamp : '';
  }

  /**
   * @param $group_id
   * @param $name
   * @param $body
   * @param $entity_type
   * @param $entity_id
   * @param $user_id
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  function addChatItem($group_id, $name, $body, $entity_type, $entity_id, $user_id = null){
    // Get group id, if not provided.
    $group_id = (empty($group_id)) ? $this->groups->getCurrentGroupId(): $group_id;
    // Get user id, if not provided.
    $user_id = (empty($user_id)) ? \Drupal::currentUser()->id() : $user_id;
    // Add chat item.
    $ol_chat_item = OlChatItem::create([
      'name' => $name,
      'chat_body' => $body,
      'user_id' => $user_id,
      'group_id' => $group_id,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
    ]);
    $ol_chat_item->save();
    $id = $ol_chat_item->id();
    // Update the record with own id for chat items, to it keep all consistent.
    if($entity_type == 'chat') {
      \Drupal::database()->update('ol_chat_item')
        ->fields(['entity_id' => $id])
        ->condition('id', $id)
        ->execute();
    }
    return $id;
  }
}
