<?php

namespace Drupal\ol_stream\Services;

use Drupal\Core\Url;
use Drupal\ol_stream_item\Entity\OlStreamItem;

/**
 * Class OlStream.
 */
class OlStream{

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $renderer
   */
  protected $renderer;

  /**
   * OlMembers constructor.
   *
   * @param $members
   * @param $renderer
   */
  public function __construct($members, $renderer) {
    $this->members = $members;
    $this->renderer = $renderer;
  }

  /**
   * @param null $gid
   * @param int $offset
   * @param int $length
   *
   * @return mixed
   */
  function getStreamList($gid = null, $offset = 0, $length = 15){

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
   * @param null $gid
   *
   * @return mixed
   */
  function getUserStreamList($uid = null, $num_per_page = null, $offset = null, $get_total = false, $gid = null){

    // Get group id's of current user, if this request wasn't from a single group.
    $current_user_gids = null;
    if(!$gid) {
      $current_user_gids = $this->getUserGroups();
    }
    // Get message data.
    $query = \Drupal::database()->select('ol_stream_item', 'osi');
    $query->addField('osi', 'id');
    $query->addField('osi', 'created');
    $query->addField('osi', 'user_id');
    $query->addField('osi', 'stream_body');
    $query->addField('osi', 'group_id');
    $query->addField('osi', 'entity_type');
    $query->addField('osi', 'entity_id');
    $query->addField('osi', 'name', 'stream_item_name');
    $query->addField('ufd', 'name');
    $query->addField('olg', 'name','group_name');
    $query->condition('olg.status', 1);
    $query->condition('osi.status', 1);

    // This is optional for user profile page.
    if($uid) {
      $query->condition('osi.user_id', $uid);
    }

    // if $gid, then source is from a group.
    if ($gid) {
      $query->condition('osi.group_id', $gid);
    }
    // This is main home stream, that contains all groups user is in.
    elseif (is_array($current_user_gids)) {
      $query->condition('osi.group_id', $current_user_gids, 'IN');
    }

    // Joins and order.
    $query->join('users_field_data', 'ufd', 'ufd.uid = osi.user_id');
    $query->join('ol_group', 'olg', 'olg.id = osi.group_id');
    $query->orderBy('osi.id', 'desc');

    // Data for list.
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
    // To do: Make query efficient so no overhead is caused there.
    return array_unique($groups_array);
  }

  /**
   * @param $stream_items
   * @param null $recent_on_top
   *
   * @return string
   */
  function renderStreamList($stream_items, $recent_on_top = null){
    // Sort stream items.
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
      $stream_row_data['files'] = '';
      $stream_row_data['user_picture_url'] = $this->members->getUserPictureUrl($stream_item->user_id);
      // If stream_date is today, show 'time ago', else show hard date.
      $stream_date = $stream_item->created;
      $stream_row_data['created'] = (date('d-m-Y') == date('d-m-Y', $stream_date)) ? date('H:i', $stream_date) : date('D, d M Y, H:i', $stream_date);
      $stream_row_data['user_name'] = $stream_item->name;
      $stream_row_data['uid'] = $stream_item->user_id;
      $stream_row_data['stream_body'] = detectAndCreateLink($stream_item->stream_body);
      $stream_row_data['path'] = $this->getStreamItemLink($stream_item);
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  function renderStreamListMulti($stream_data, $group_ids){

    $active_date = null;
    $body_text_labels = $this->getBodyTextLabels();
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
              $stream_row_item['icon_class'] = $this->getSectionIconClass($stream_item->entity_type);
              $stream_row_item['body_text_label'] = $body_text_labels[$stream_item->stream_item_name];
              $stream_row_item['stream_body'] = htmlspecialchars_decode($stream_item->stream_body);
              $link_item = $this->getStreamItemLink($stream_item);
              $stream_row_item['path'] = $link_item['path'];
              $stream_row_item['link_label'] = (!empty($link_item['label'])) ? $link_item['label'] : false;
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
          $stream_wrapper['in_group'] = \Drupal::service('current_route_match')->getParameter('gid');
          // We can't have this as dependency, else install profile will bitch during install.
          // So for now, procedural use of this service.
          $groups = \Drupal::service('olmain.groups');
          $stream_wrapper['group_name'] = $groups->getGroupName($group_id);
          $stream_wrapper['group_thumbnail_url'] = $this->getGroupImageLink($group_id);
          $stream_wrapper['stream_html'] = $item_render_html;
          $render = ['#theme' => 'stream_wrapper', '#vars' => $stream_wrapper];
          $stream_html .= \Drupal::service('renderer')->render($render);
        }
      }
    }
    return $stream_html;
  }

  /**
   * @param $entity_type
   *
   * @return string|null
   */
  private function getSectionIconClass($entity_type){
    // Get sections data.
    $sections_service = \Drupal::service('olmain.sections');
    $sections = $sections_service->getSectionsData();
    // Fill icon_class.
    foreach ($sections as $section){
      if ((string) $section['path'] == $entity_type){
        $icon_class = (string) $section['icon_class']; // Casting to string is needed here.
      }
    }
    // If icon class not filled yet, check if it's a 'left over type'.
    if (empty($icon_class)){
      $left_overs_types = [
        'comment' =>  'lni lni-comments',
        'user' =>  'lni lni-user',
        'category' => 'lni lni-tag',
        'folder' => 'lni lni-folder',
      ];
      if(!empty($left_overs_types[$entity_type])) {
        $icon_class = $left_overs_types[$entity_type];
      }
    }
    return (!empty($icon_class)) ? $icon_class : null ;
  }


  /**
   * @param $group_id
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getGroupImageLink($group_id){
    $groups = \Drupal::service('olmain.groups');
    $header_fid = $groups->getHeaderImage($group_id);
    if (!empty($header_fid)) {
      $files = \Drupal::service('olmain.files');
      $header_uri = $files->getFileUri($header_fid);
      $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('50x50');
      return $style->buildUrl($header_uri);
    }
  }

  /**
   * @param $file_ids_json
   *
   * @return array
   */
  private function getFileLinks($file_ids_json){
    $file_ids = json_decode($file_ids_json, true);
    $files = array();
    foreach ($file_ids as $file_id) {
      $files = \Drupal::service('olmain.files');
      $files[$file_id]['file_name'] = $files->getFileName($file_id);
      $file_uri = $files->getFileUri($file_id);
      $files[$file_id]['file_path'] = Url::fromUri(file_create_url($file_uri));
    }
    return $files;
  }

  /**
   * @return array
   */
  private function getBodyTextLabels(){
    $labels = [
      'user_added' => t('added a user to the group'),
      'user_removed' => t('remove a user from the group'),
      'comment_added' => t('added a comment'),
      'message_added' => t('added a message'),
      'post_added' => t('added a post'),
      'text_doc_removed' => t('removed a notebook'),
    ];
    // Invoke hook to add labels from other modules.
    $external_labels = \Drupal::moduleHandler()->invokeAll('stream_item_body_labels');
    return array_merge($external_labels, $labels);
  }

  /**
   * @param $stream_item
   *
   * @return array
   */
  private function  getStreamItemLink($stream_item){

    // Check if an external modules must be involved to generate link.
    $link_item = \Drupal::moduleHandler()->invokeAll('stream_item_links', [$stream_item]);

    // Return if there is a match in hooked modules.
    if(!empty($link_item)) {
      return $link_item;
    }
    // Init.
    $label = '';
    // If $path still empty, than it's a core item, find it here.
    switch ($stream_item->entity_type) {
      // Content.
      case 'notebooks':
        $path = Url::fromRoute('ol_text_docs.text_doc', ['gid' => $stream_item->group_id, 'id' => $stream_item->entity_id])->toString();
      break;
      case 'messages':
        $path = Url::fromRoute('lus_message.message', ['gid' => $stream_item->group_id, 'id' => $stream_item->entity_id])->toString();
      break;
      case 'posts':
        $path = Url::fromRoute('lus_post.posts', ['gid' => $stream_item->group_id])->toString();
      break;
      case 'folder':
        $path = Url::fromRoute('ol_files.group_files', ['gid' => $stream_item->group_id, 'folder' => $stream_item->entity_id])->toString();
      break;
      // Files.
      case 'files':
        $file_uri = $this->files->getFileUri($stream_item->entity_id);
        if ($file_uri) {
          $path = Url::fromUri(file_create_url($file_uri));
        }
      break;
      // Comments.
      case 'comment':
        // Get comment data.
        $comments = \Drupal::service('olmain.comments');
        $comment_data = $comments->getCommentData($stream_item->entity_id);
        // Edge cases fallback.
        if(empty($comment_data->entity_type)){
          break;
        }
        // Check if an external modules must be involved to generate link for this comment.
        $link_item = \Drupal::moduleHandler()->invokeAll('add_comment_links', [$comment_data, $stream_item->group_id]);
        // Return if there is a $path for this comment found via above hook.
        if(!empty($link_item)) {
          return $link_item;
        }
        // Core comments.
        if($comment_data->entity_type == 'post') {
          $path = Url::fromRoute('lus_post.posts', ['gid' => $stream_item->group_id])->toString();
        }
        if($comment_data->entity_type == 'message') {
          $path = Url::fromRoute('lus_message.message',['gid' => $stream_item->group_id, 'id' => $comment_data->entity_id])->toString();
        }
        if($comment_data->entity_type == 'text_doc') {
          $path = Url::fromRoute('ol_text_docs.text_doc', ['gid' => $stream_item->group_id, 'id' => $comment_data->entity_id])->toString();
        }
      break; // End "case 'comment'":
    }
    if(!empty($path)) {
      return [
        'path' => $path,
        'label' => $label,
      ];
    }
  }

  /**
   * @param $uuid
   *
   * @return int|string
   */
  function getLastMessageTimestamp($uuid){
    // Get internal group_id from uuid.
    $groups = \Drupal::service('olmain.groups');
    $group_id = $groups->getGroupIdByUuid($uuid);
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
