<?php

namespace Drupal\ol_stream\Services;

use Drupal\Core\Url;
use Drupal\ol_stream_item\Entity\OlStreamItem;

/**
 * Class OlStream.
 */
class OlStream{

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
   * @param null $gid
   * @param int $offset
   * @param int $length
   *
   * @return mixed
   */
  function getStreamList($gid = null, $offset = 0, $length = 15){
    // Get plain group_id from uuid.
    //$group_id = $this->groups->getGroupIdByUuid($group_uuid);
    // Get message data.
    $query = \Drupal::database()->select('ol_stream_item', 'osi');
    $query->addField('osi', 'id');
    $query->addField('osi', 'created');
    $query->addField('osi', 'user_id');
    $query->addField('osi', 'stream_body');
    $query->addField('osi', 'group_id');
    $query->addField('osi', 'entity_type');
    $query->addField('osi', 'entity_id');
    $query->addField('ufd', 'name');
    $query->condition('osi.group_id', $gid);
    $query->condition('osi.status', 1);
    $query->join('users_field_data', 'ufd', 'ufd.uid = osi.user_id');
    $query->orderBy('osi.id', 'desc');
    $query->range($offset, $length);
    return $query->execute()->fetchAll();
  }

  /**
   * @param $uid
   * @param null $num_per_page
   * @param int $offset
   * @param bool $get_total
   *
   * @return mixed
   */
  function getUserStreamList($uid = null, $num_per_page = null, $offset = null, $get_total = false){

    // Get group id's of current user, to only query that data.
    $current_user_gids = $this->getUserGroups();
   // print_r($current_user_gids);
    // Get message data.
    $query = \Drupal::database()->select('ol_stream_item', 'osi');
    $query->addField('osi', 'id');
    $query->addField('osi', 'created');
    $query->addField('osi', 'user_id');
    $query->addField('osi', 'stream_body');
    $query->addField('osi', 'group_id');
    $query->addField('osi', 'entity_type');
    $query->addField('osi', 'entity_id');
    $query->addField('ufd', 'name');
    $query->addField('olg', 'name','group_name');
    // This is optional for User profile page.
    if($uid) {
      $query->condition('osi.user_id', $uid);
    }
    $query->condition('osi.status', 1);
    $query->condition('osi.group_id', $current_user_gids, 'IN');
    $query->join('users_field_data', 'ufd', 'ufd.uid = osi.user_id');
    $query->join('ol_group', 'olg', 'olg.id = osi.group_id');
    $query->orderBy('osi.id', 'desc');
    // Data for message list.
    if ($get_total == false) {
      $query->range($offset, $num_per_page);
      $stream_data = $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == true) {
      $stream_data = $query->countQuery()->execute()->fetchField();
    }
    return $stream_data;
  }

  /**
   * @param null $uid
   * @param bool $order_by_activity
   *
   * @return array
   */
  public function getUserGroups($uid = null, $order_by_activity = false){
    // Get current uid if non provided.
    $uid = ($uid) ? $uid : \Drupal::currentUser()->id();
    // Query group id's of current user.
    $query = \Drupal::database()->select('ol_group_user', 'olg');
    $query->addField('olg', 'group_id');
    $query->condition('olg.member_uid', $uid);
    // Join is needed to make sure group with latest activity is on top.
    if($order_by_activity) {
      $query->join('ol_stream_item', 'osi', 'osi.group_id = olg.group_id');
      $query->orderBy('osi.id', 'desc');
    } else {
      $query->orderBy('olg.name', 'desc');
    }
    $groups = $query->execute()->fetchAll();
    // Build usable array.
    $groups_array = array();
    foreach ($groups as $group){
      array_push($groups_array, $group->group_id);
    }
    // The join to order groups on activity above produces duplicate group_ids.
    // TODO: Make query efficient so no overhead is caused there.
    return array_unique($groups_array);
  }

  /**
   * @param $stream_items
   * @param null $recent_on_top
   *
   * @return string
   */
  function renderStreamList($stream_items, $recent_on_top = null){
    if ($recent_on_top != true) {
      // Sort array, so newest stream items will be on bottom
      usort($stream_items, function ($a, $b) {
        return strcmp($a->created, $b->created);
      });
    }
    // Initiate html var.
    $stream_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($stream_items as $stream_item){
      // Set last_id, for load more button.
      $stream_row_data['files'] = '';
      $stream_row_data['user_picture_url'] = $this->members->getUserPictureUrl($stream_item->user_id);
      // If stream_date is today, show 'time ago', else show hard date.
      $stream_date = $stream_item->created;
      $stream_row_data['created'] = (date('d-m-Y') == date('d-m-Y', $stream_date)) ? date('H:i', $stream_date) : date('D, d M Y, H:i', $stream_date);
      $stream_row_data['user_name'] = $stream_item->name;
      $stream_row_data['uid'] = $stream_item->user_id;
//      $stream_row_data['group_name'] = $stream_item->group_name;
      $stream_row_data['stream_body'] = detectAndCreateLink($stream_item->stream_body);
      $stream_row_data['path'] = $this->getStreamItemLink($stream_item);
      //$stream_row_data['item_type'] = (!empty($stream_row_data['path'])) ? 'external': 'chat';
      if ($stream_item->entity_type == 'files_added'){
        $stream_row_data['files'] = $this->getFileLinks($stream_item->entity_id);
        $stream_row_data['stream_body'] = t('Added files: ');
      }
      $render = ['#theme' => 'stream_item', '#vars' => $stream_row_data];
      $stream_html .= $this->renderer->render($render);
    }
    return $stream_html;
  }

  /**
   * @param $stream_data
   * @param $group_ids
   *
   * @return string
   */
  function renderStreamListMulti($stream_data, $group_ids){

    $active_date = null;
    foreach ($stream_data as $key => $stream_item) {
      // Create readable date.
      $current_date = date('d M Y', $stream_item->created);
      // Add readable date to objects in $stream_data array.
      $stream_data[$key]->readable_date = $current_date;
      // Build $dates_array, so we know what dates we have to loop though beneath
      if($current_date != $active_date) {
        $dates[] = $current_date;
        $active_date = $current_date;
      }
    }
    $stream_html = '';
    // Loop through selected dates.
    if(empty($dates)){
      return null;
    }
    foreach ($dates as $date) {
      $stream_wrapper['readable_date'] = $date;
      // Loop through groups current user is in.
      foreach ($group_ids as $group_id) {
        $item_render_html = '';
        $show_this_block = false;
        foreach ($stream_data as $stream_item) {
          if ($stream_item->group_id == $group_id and $stream_item->readable_date == $date) {
            // Flag to show group title, if there is content in this day for this group.
            $stream_row_item = array();
            if(!empty($stream_item->stream_body)){
              // Fill item
              $show_this_block = true;
              $stream_row_item['stream_body'] = $stream_item->stream_body;
              $stream_row_item['path'] = $this->getStreamItemLink($stream_item);
              $stream_row_item['created'] = date('H:m',$stream_item->created);
              $stream_row_item['user_picture_url'] = $this->members->getUserPictureUrl($stream_item->user_id);
              $stream_row_item['user_name'] = $stream_item->name;
              $stream_row_item['uid'] = $stream_item->user_id;
              if ($stream_item->entity_type == 'files_added'){
                $stream_row_item['files'] = $this->getFileLinks($stream_item->entity_id);
                $stream_row_item['stream_body'] = t('Added files: ');
              }
              $item_render = ['#theme' => 'stream_item', '#vars' => $stream_row_item];
              $item_render_html .= \Drupal::service('renderer')->render($item_render);
            }
          }
        }
        if($show_this_block){
          $stream_wrapper['group_id'] = $group_id;
          $stream_wrapper['group_name'] = $this->groups->getGroupName($group_id);
          $stream_wrapper['stream_html'] = $item_render_html;
          $render = ['#theme' => 'stream_wrapper', '#vars' => $stream_wrapper];
          $stream_html .= \Drupal::service('renderer')->render($render);
        }
      }
    }
    return $stream_html;
  }

  private function getFileLinks($file_ids_json){
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
   * @param $stream_item
   * @return string
   */
  private function getStreamItemLink($stream_item){
    // This should be more dynamic, so hooked modules will also get involved.
    $path = '';
    switch ($stream_item->entity_type) {
      case 'text_doc':
        $path = Url::fromRoute('ol_files.text_doc',
          ['gid' => $stream_item->group_id, 'id' => $stream_item->entity_id])->toString();
      break;
      case 'message':
        $path = Url::fromRoute('lus_message.message',
          ['gid' => $stream_item->group_id, 'id' => $stream_item->entity_id])->toString();
      break;
      case 'file_added':
        $file_uri = $this->files->getFileUri($stream_item->entity_id);
        $path = Url::fromUri(file_create_url($file_uri));
      break;
      case 'icebreaker':
        $path = Url::fromRoute('ol_icebreakers.list_page',
          ['gid' => $stream_item->group_id])->toString();
      break;
      case 'culture_question':
        $path = Url::fromRoute('ol_culture_questions.detail_page',
          ['gid' => $stream_item->group_id, 'id' => $stream_item->entity_id])->toString();
      break;
      case 'post':
        $path = Url::fromRoute('lus_post.posts',
          ['gid' => $stream_item->group_id])->toString();
      break;
      case 'shoutout':
        $path = Url::fromRoute('ol_shoutouts.list_page',
          ['gid' => $stream_item->group_id])->toString();
      break;
      case 'comment':
        // Get extra comment data and switch based on entity type
        $comment_data = $this->getStreamItemCommentData($stream_item->entity_id);
        // Needed for privacy = 1 comments.
        if(empty($comment_data->entity_type)){
          break;
        };
        if($comment_data->entity_type == 'post') {
          $path = Url::fromRoute('lus_post.posts',
            ['gid' => $stream_item->group_id])->toString();
        }
        if($comment_data->entity_type == 'culture_question') {
          $path = Url::fromRoute('ol_culture_questions.detail_page',
            ['gid' => $stream_item->group_id, 'id' => $comment_data->entity_id])->toString();
        }
        if($comment_data->entity_type == 'message') {
          $path = Url::fromRoute('lus_message.message',
            ['gid' => $stream_item->group_id, 'id' => $comment_data->entity_id])->toString();
        }
      break;
    }
    return $path;
  }
  private function getStreamItemCommentData($comment_id){
    // Query for me, b*tch
    $query = \Drupal::database()->select('ol_comment', 'olc');
    $query->addField('olc', 'entity_type');
    $query->addField('olc', 'entity_id');
    $query->condition('olc.id', $comment_id);
    $query->condition('olc.status', 1);
    return $query->execute()->fetchObject();
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
    $query = \Drupal::database()->select('ol_stream_item', 'osi');
    $query->addField('osi', 'created');
    $query->condition('osi.group_id', $group_id);
    $query->condition('osi.status', 1);
    $query->orderBy('osi.created', 'desc');
    $query->range(0, 1);
    $timestamp = $query->execute()->fetchField();
    // Return empty string if no result, to prevent error on empty streams.
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
  function addStreamItem($group_id, $name, $body, $entity_type, $entity_id, $user_id = null){
    // Get user id, if not provided.
    $user_id = (empty($user_id)) ? \Drupal::currentUser()->id() : $user_id;
    // Add stream item.
    $ol_stream_item = OlStreamItem::create([
      'name' => $name,
      'stream_body' => $body,
      'user_id' => $user_id,
      'group_id' => $group_id,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
    ]);
    $ol_stream_item->save();
    $id = $ol_stream_item->id();
    // Update the record with own id for chat items, to it keep all consistent.
    if($entity_type == 'chat') {
      \Drupal::database()->update('ol_stream_item')
        ->fields(['entity_id' => $id])
        ->condition('id', $id)
        ->execute();
    }
  }
}
