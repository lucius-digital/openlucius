<?php

namespace Drupal\ol_stream\Services;

use Drupal\Core\Url;
use Drupal\ol_stream_item\Entity\OlStreamItem;

/**
 * Service class for managing stream items.
 *
 * @category Services
 * @package OpenLucius
 * @author OpenLucius Team <openlucius@example.com>
 * @license https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link https://github.com/openlucius/openlucius
 */
class OlStream {

  /**
   * The members service.
   *
   * @var mixed
   */
  protected $members;

  /**
   * The renderer service.
   *
   * @var mixed
   */
  protected $renderer;

  /**
   * Constructs an OlStream object.
   *
   * @param mixed $members
   *   The members service for user operations.
   * @param mixed $renderer
   *   The renderer service for template rendering.
   */
  public function __construct($members, $renderer) {
    $this->members = $members;
    $this->renderer = $renderer;
  }

  /**
   * Get a list of stream items for a group.
   *
   * @param mixed $gid
   *   The group ID to filter stream items.
   * @param int $offset
   *   The offset for pagination starting point.
   * @param int $length
   *   The number of items to return per page.
   *
   * @return mixed
   *   The stream items.
   */
  public function getStreamList($gid = NULL, $offset = 0, $length = 15) {

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
   * Get the user's stream list with optional filtering.
   *
   * @param mixed $uid
   *   The user ID to filter stream items by user.
   * @param mixed $num_per_page
   *   Number of items to return per page.
   * @param mixed $offset
   *   The offset for pagination starting point.
   * @param bool $get_total
   *   Whether to get the total count instead of items.
   * @param mixed $gid
   *   The group ID to filter stream items by group.
   *
   * @return mixed
   *   The stream data.
   */
  public function getUserStreamList(
    $uid = NULL,
    $num_per_page = NULL,
    $offset = NULL,
    $get_total = FALSE,
    $gid = NULL,
  ) {

    // Get group IDs of current user if not from a single group.
    $current_user_gids = NULL;
    if (!$gid) {
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
    $query->addField('olg', 'name', 'group_name');
    $query->condition('olg.status', 1);
    $query->condition('osi.status', 1);

    // This is optional for user profile page.
    if ($uid) {
      $query->condition('osi.user_id', $uid);
    }

    // If $gid, then source is from a group.
    if ($gid) {
      $query->condition('osi.group_id', $gid);
    }
    elseif (is_array($current_user_gids)) {
      $query->condition('osi.group_id', $current_user_gids, 'IN');
    }

    // Joins and order.
    $query->join('users_field_data', 'ufd', 'ufd.uid = osi.user_id');
    $query->join('ol_group', 'olg', 'olg.id = osi.group_id');
    $query->orderBy('osi.id', 'desc');

    // Data for list.
    if ($get_total == FALSE) {
      $query->range($offset, $num_per_page);
      $stream_data = $query->execute()->fetchAll();
    }
    elseif ($get_total == TRUE) {
      $stream_data = $query->countQuery()->execute()->fetchField();
    }

    return $stream_data;
  }

  /**
   * Get the group IDs for a user.
   *
   * @param mixed $uid
   *   The user ID to get groups for.
   * @param bool $order_by_activity
   *   Whether to order groups by latest activity.
   *
   * @return array
   *   Array of group IDs.
   */
  public function getUserGroups($uid = NULL, $order_by_activity = FALSE) {
    // Get current uid if non provided.
    $uid = ($uid) ? $uid : \Drupal::currentUser()->id();
    // Query group id's of current user.
    $query = \Drupal::database()->select('ol_group_user', 'olg');
    $query->addField('olg', 'group_id');
    $query->condition('olg.member_uid', $uid);
    // Join is needed to make sure group with latest activity is on top.
    if ($order_by_activity) {
      $query->join('ol_stream_item', 'osi', 'osi.group_id = olg.group_id');
      $query->orderBy('osi.id', 'desc');
    }
    else {
      $query->orderBy('olg.name', 'desc');
    }
    $groups = $query->execute()->fetchAll();
    // Build usable array.
    $groups_array = [];
    foreach ($groups as $group) {
      array_push($groups_array, $group->group_id);
    }
    // The join to order groups on activity above produces duplicate group_ids.
    // To do: Make query efficient so no overhead is caused there.
    return array_unique($groups_array);
  }

  /**
   * Render a list of stream items as HTML.
   *
   * @param array $stream_items
   *   The stream items array to render as HTML.
   * @param mixed $recent_on_top
   *   Whether to show recent items on top of list.
   *
   * @return string
   *   The rendered HTML.
   */
  public function renderStreamList($stream_items, $recent_on_top = NULL) {
    // Sort stream items.
    if ($recent_on_top != TRUE) {
      // Sort array, so newest stream items will be on bottom.
      usort(
            $stream_items, function ($a, $b) {
                return strcmp($a->created, $b->created);
            }
        );
    }
    // Initiate html var.
    $stream_html = '';
    // Loop through array and render HTML rows via twig file.
    foreach ($stream_items as $stream_item) {
      $stream_row_data['files'] = '';
      $stream_row_data['user_picture_url'] = $this->members->getUserPictureUrl(
            $stream_item->user_id
        );
      // If stream_date is today, show 'time ago', else show hard date.
      $stream_date = $stream_item->created;
      $today = date('d-m-Y');
      $formatted = date('d-m-Y', $stream_date);
      $stream_row_data['created'] = ($today == $formatted)
                ? date('H:i', $stream_date)
                : date('D, d M Y, H:i', $stream_date);
      $stream_row_data['user_name'] = $stream_item->name;
      $stream_row_data['uid'] = $stream_item->user_id;
      $stream_row_data['stream_body'] = detectAndCreateLink(
            $stream_item->stream_body
        );
      $stream_row_data['path'] = $this->getStreamItemLink($stream_item);
      if ($stream_item->entity_type == 'files_added') {
        $stream_row_data['files'] = $this->getFileLinks(
          $stream_item->entity_id
          );
        $stream_row_data['stream_body'] = t('Added files:');
      }
      $render = ['#theme' => 'stream_item', '#vars' => $stream_row_data];
      $stream_html .= $this->renderer->render($render);
    }
    return $stream_html;
  }

  /**
   * Render a multi-group stream list as HTML.
   *
   * @param array $stream_data
   *   The stream data array to render as HTML.
   * @param array $group_ids
   *   The group IDs to include in the render.
   *
   * @return string
   *   The rendered HTML.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function renderStreamListMulti($stream_data, $group_ids) {

    $active_date = NULL;
    $body_text_labels = $this->getBodyTextLabels();
    foreach ($stream_data as $key => $stream_item) {
      // Create readable date.
      $current_date = date('d M Y', $stream_item->created);
      // Add readable date to objects in $stream_data array.
      $stream_data[$key]->readable_date = $current_date;
      // Build $dates_array for looping beneath.
      if ($current_date != $active_date) {
        $dates[] = $current_date;
        $active_date = $current_date;
      }
    }
    $stream_html = '';
    // Loop through selected dates.
    if (empty($dates)) {
      return NULL;
    }
    foreach ($dates as $date) {
      $stream_wrapper['readable_date'] = $date;
      // Loop through groups current user is in.
      foreach ($group_ids as $group_id) {
        $item_render_html = '';
        $show_this_block = FALSE;
        foreach ($stream_data as $stream_item) {
          $is_match = ($stream_item->group_id == $group_id
                && $stream_item->readable_date == $date
            );
          if ($is_match) {
            // Flag to show group title if there is content.
            $stream_row_item = [];
            if (!empty($stream_item->stream_body)) {
              // Fill item.
              $show_this_block = TRUE;
              $icon_class = $this->getSectionIconClass(
                    $stream_item->entity_type
                );
              $stream_row_item['icon_class'] = $icon_class;
              $item_name = $stream_item->stream_item_name;
              $stream_row_item['body_text_label'] = $body_text_labels[$item_name];
              $stream_row_item['stream_body'] = htmlspecialchars_decode(
                    $stream_item->stream_body
                );
              $link_item = $this->getStreamItemLink($stream_item);
              if (!empty($link_item['path'])) {
                $stream_row_item['path'] = $link_item['path'];
              }
              $has_label = !empty($link_item['label']);
              $stream_row_item['link_label'] = $has_label
                                ? $link_item['label'] : FALSE;
              $stream_row_item['created'] = date('H:m', $stream_item->created);
              $pic_url = $this->members->getUserPictureUrl(
                    $stream_item->user_id
                );
              $stream_row_item['user_picture_url'] = $pic_url;
              $stream_row_item['user_name'] = $stream_item->name;
              $stream_row_item['uid'] = $stream_item->user_id;
              if ($stream_item->entity_type == 'files_added') {
                $stream_row_item['files'] = $this->getFileLinks(
                $stream_item->entity_id
                );
                $stream_row_item['stream_body'] = t('Added files:');
              }
              $item_render = [
                '#theme' => 'stream_item',
                '#vars' => $stream_row_item,
              ];
              $item_render_html .= \Drupal::service('renderer')
                ->render($item_render);
            }
          }
        }
        if ($show_this_block) {
          $stream_wrapper['group_id'] = $group_id;
          $route_match = \Drupal::service('current_route_match');
          $stream_wrapper['in_group'] = $route_match->getParameter('gid');
          // Procedural use to avoid install dependency issues.
          $groups = \Drupal::service('olmain.groups');
          $stream_wrapper['group_name'] = $groups->getGroupName($group_id);
          $thumb_url = $this->getGroupImageLink($group_id);
          $stream_wrapper['group_thumbnail_url'] = $thumb_url;
          $stream_wrapper['stream_html'] = $item_render_html;
          $render = [
            '#theme' => 'stream_wrapper',
            '#vars' => $stream_wrapper,
          ];
          $stream_html .= \Drupal::service('renderer')->render($render);
        }
      }
    }
    return $stream_html;
  }

  /**
   * Get the icon class for a section entity type.
   *
   * @param string $entity_type
   *   The entity type to get icon class for.
   *
   * @return string|null
   *   The icon class string or null if not found.
   */
  private function getSectionIconClass($entity_type) {
    // Get sections data.
    $sections_service = \Drupal::service('olmain.sections');
    $sections = $sections_service->getSectionsData();
    // Fill icon_class.
    foreach ($sections as $section) {
      if ((string) $section['path'] == $entity_type) {
        $icon_class = (string) $section['icon_class'];
      }
    }
    // If icon class not filled yet, check if it's a 'left over type'.
    if (empty($icon_class)) {
      $left_overs_types = [
        'comment' => 'lni lni-comments',
        'user' => 'lni lni-user',
        'category' => 'lni lni-tag',
        'folder' => 'lni lni-folder',
      ];
      if (!empty($left_overs_types[$entity_type])) {
        $icon_class = $left_overs_types[$entity_type];
      }
    }
    return (!empty($icon_class)) ? $icon_class : NULL;
  }

  /**
   * Get the group image link URL.
   *
   * @param int $group_id
   *   The group ID to get image for.
   *
   * @return mixed
   *   The image URL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getGroupImageLink($group_id) {
    $groups = \Drupal::service('olmain.groups');
    $header_fid = $groups->getHeaderImage($group_id);
    if (!empty($header_fid)) {
      $files = \Drupal::service('olmain.files');
      $header_uri = $files->getFileUri($header_fid);
      $styleStorage = \Drupal::entityTypeManager()->getStorage('image_style');
      $style = $styleStorage->load('50x50');
      return $style->buildUrl($header_uri);
    }
  }

  /**
   * Get file links from a JSON string of file IDs.
   *
   * @param string $file_ids_json
   *   JSON encoded string containing file IDs.
   *
   * @return array
   *   Array of file link data.
   */
  private function getFileLinks($file_ids_json) {
    $file_ids = json_decode($file_ids_json, TRUE);
    $files = [];
    foreach ($file_ids as $file_id) {
      $filesService = \Drupal::service('olmain.files');
      $files[$file_id]['file_name'] = $filesService->getFileName($file_id);
      $file_uri = $filesService->getFileUri($file_id);
      $file_url = Url::fromUri(file_create_url($file_uri));
      $files[$file_id]['file_path'] = $file_url;
    }
    return $files;
  }

  /**
   * Get the body text labels for stream items.
   *
   * @return array
   *   Array of label key-value pairs.
   */
  private function getBodyTextLabels() {
    $labels = [
      'user_added' => t('added a user to the group'),
      'user_removed' => t('remove a user from the group'),
      'comment_added' => t('added a comment'),
      'message_added' => t('added a message'),
      'post_added' => t('added a post'),
      'text_doc_removed' => t('removed a notebook'),
    ];
    // Invoke hook to add labels from other modules.
    $moduleHandler = \Drupal::moduleHandler();
    $external_labels = $moduleHandler->invokeAll('stream_item_body_labels');
    return array_merge($external_labels, $labels);
  }

  /**
   * Get the link data for a stream item.
   *
   * @param object $stream_item
   *   The stream item object to get link data for.
   *
   * @return array
   *   Array with path and label keys.
   */
  private function getStreamItemLink($stream_item) {

    // Check if external modules generate the link.
    $link_item = \Drupal::moduleHandler()
      ->invokeAll('stream_item_links', [$stream_item]);

    // Return if there is a match in hooked modules.
    if (!empty($link_item)) {
      return $link_item;
    }
    // Init.
    $label = '';
    // If $path still empty, than it's a core item, find it here.
    switch ($stream_item->entity_type) {
      // Content.
      case 'notebooks':
        $params = [
          'gid' => $stream_item->group_id,
          'id' => $stream_item->entity_id,
        ];
        $path = Url::fromRoute('ol_text_docs.text_doc', $params)->toString();
        break;

      case 'messages':
        $params = [
          'gid' => $stream_item->group_id,
          'id' => $stream_item->entity_id,
        ];
        $path = Url::fromRoute('lus_message.message', $params)->toString();
        break;

      case 'posts':
        $params = ['gid' => $stream_item->group_id];
        $path = Url::fromRoute('lus_post.posts', $params)->toString();
        break;

      case 'folder':
        $params = [
          'gid' => $stream_item->group_id,
          'folder' => $stream_item->entity_id,
        ];
        $path = Url::fromRoute('ol_files.group_files', $params)->toString();
        break;

      // Files, TODO
      // case 'files':
      //  if ($stream_item->entity_id) {
      //    $file_uri = $this->files->getFileUri($stream_item->entity_id);
      //  }
      //  if ($file_uri) {
      //    $path = Url::fromUri(file_create_url($file_uri));
      //  }
      //  break;
      // Comments.
      case 'comment':
        // Get comment data.
        $comments = \Drupal::service('olmain.comments');
        $comment_data = $comments->getCommentData($stream_item->entity_id);
        // Edge cases fallback.
        if (empty($comment_data->entity_type)) {
          break;
        }
        // Check if external modules generate the link.
        $hook_args = [$comment_data, $stream_item->group_id];
        $link_item = \Drupal::moduleHandler()
          ->invokeAll('add_comment_links', $hook_args);
        // Return if there is a $path for this comment found via above hook.
        if (!empty($link_item)) {
          return $link_item;
        }
        // Core comments.
        if ($comment_data->entity_type == 'post') {
          $params = ['gid' => $stream_item->group_id];
          $path = Url::fromRoute('lus_post.posts', $params)->toString();
        }
        if ($comment_data->entity_type == 'message') {
          $params = [
            'gid' => $stream_item->group_id,
            'id' => $comment_data->entity_id,
          ];
          $path = Url::fromRoute('lus_message.message', $params)->toString();
        }
        if ($comment_data->entity_type == 'text_doc') {
          $params = [
            'gid' => $stream_item->group_id,
            'id' => $comment_data->entity_id,
          ];
          $path = Url::fromRoute('ol_text_docs.text_doc', $params)->toString();
        }
        // End "case 'comment'":
        break;

    }
    if (!empty($path)) {
      return [
        'path' => $path,
        'label' => $label,
      ];
    }
  }

  /**
   * Get the last message timestamp for a group.
   *
   * @param string $uuid
   *   The group UUID to get timestamp for.
   *
   * @return int|string
   *   The timestamp or empty string if not found.
   */
  public function getLastMessageTimestamp($uuid) {
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
   * Add a new stream item.
   *
   * @param int $group_id
   *   The group ID to add the stream item to.
   * @param string $name
   *   The stream item name identifier.
   * @param string $body
   *   The stream body text content.
   * @param string $entity_type
   *   The entity type associated with the item.
   * @param mixed $entity_id
   *   The entity ID reference.
   * @param mixed $user_id
   *   The user ID who created the item.
   *
   * @return void
   *   Returns nothing.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addStreamItem(
    $group_id,
    $name,
    $body,
    $entity_type,
    $entity_id,
    $user_id = NULL,
  ) {
    // Get user id, if not provided.
    $user_id = (empty($user_id)) ? \Drupal::currentUser()->id() : $user_id;
    // Add stream item.
    $ol_stream_item = OlStreamItem::create(
          [
            'name' => $name,
            'stream_body' => $body,
            'user_id' => $user_id,
            'group_id' => $group_id,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
          ]
      );
    $ol_stream_item->save();
    $id = $ol_stream_item->id();
    // Update the record with own id for chat items, to it keep all consistent.
    if ($entity_type == 'chat') {
      \Drupal::database()->update('ol_stream_item')
        ->fields(['entity_id' => $id])
        ->condition('id', $id)
        ->execute();
    }
  }

}
