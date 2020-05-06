<?php

namespace Drupal\ol_stream\Services;

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
   * OlMembers constructor.
   * @param $route
   * @param $connection
   * @param $messenger
   * @param $current_user
   * @param $entity_type_manager
   */
  public function __construct($groups, $members, $renderer) {
    $this->groups = $groups;
    $this->members = $members;
    $this->renderer = $renderer;
  }

  /**
   * @param $group_id
   * @param int $offset
   * @param int $length
   * @return mixed
   */
  function getStreamList($group_uuid, $offset = 0, $length = 15){
    // Get plain group_id from uuid.
    $group_id = $this->groups->getGroupIdByUuid($group_uuid);
    // Get message data.
    $query = \Drupal::database()->select('ol_stream_item', 'osi');
    $query->addField('osi', 'id');
    $query->addField('osi', 'created');
    $query->addField('osi', 'user_id');
    $query->addField('osi', 'stream_body');
    $query->addField('osi', 'group_id');
    $query->addField('ufd', 'name');
    $query->condition('osi.group_id', $group_id);
    $query->condition('osi.status', 1);
    $query->join('users_field_data', 'ufd', 'ufd.uid = osi.user_id');
    $query->orderBy('osi.id', 'desc');
    $query->range($offset, $length);
    $stream_list = $query->execute()->fetchAll();
    return $stream_list;
  }


  /**
   * @param $stream_items
   * @return string
   * @throws \Exception
   */
  function renderStreamList($stream_items){
    // Sort array, so newest stream items will be on bottom
    usort($stream_items, function($a, $b){
      return strcmp($a->created, $b->created);
    });
    // Initiate html var.
    $stream_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($stream_items as $stream_item){
      // Set last_id, for load more button.
      $stream_row_data['user_picture_url'] = $this->members->getUserPictureUrl($stream_item->user_id);
      // If stream_date is today, show 'time ago', else show hard date.
      $stream_date = $stream_item->created;
      $stream_row_data['created'] = (date('d-m-Y') == date('d-m-Y', $stream_date)) ? date('H:i', $stream_date) : date('D, d M y, H:i', $stream_date);
      $stream_row_data['user_name'] = $stream_item->name;
      // Empty body if it's a 'Files uploaded only' item, else populate with stream_item.
      $stream_row_data['stream_body'] = ($stream_item->stream_body == 'Files_uploaded') ? '' : $stream_item->stream_body;
      $render = ['#theme' => 'stream_item', '#vars' => $stream_row_data];
      $stream_html .= $this->renderer->render($render);
    }
    return $stream_html;
  }

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
   *
   * @param $entity_type
   * @param $entity_id
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  function addStreamItem($group_id, $name, $body, $entity_type, $entity_id){
    // Add stream item.
    $ol_stream_item = OlStreamItem::create([
      'name' => $name,
      'stream_body' => $body,
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
