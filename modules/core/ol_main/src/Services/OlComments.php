<?php

namespace Drupal\ol_main\Services;

use Drupal\Core\Url;
use Drupal\ol_comment\Entity\OlComment;
use Drupal\ol_like\Form\LikeForm;
use Drupal\ol_main\Form\CommentForm;

/**
 * Service for managing comment operations.
 */
class OlComments {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route;

  /**
   * The OL members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * The OL files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * The OL notifications service.
   *
   * @var \Drupal\ol_notifications\Services\OlNotifications
   */
  protected $notifications;

  /**
   * Constructs an OlComments instance.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   The current route match.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   The OL members service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   The OL files service.
   * @param \Drupal\ol_notifications\Services\OlNotifications $notifications
   *   The OL notifications service.
   */
  public function __construct($route, $members, $files, $notifications) {
    $this->route = $route;
    $this->members = $members;
    $this->files = $files;
    $this->notifications = $notifications;
  }

  /**
   * Save a new comment.
   *
   * Privacy: 0 = visible to all; 1 = only visible to content creator;
   * 2 = invisible for externals.
   *
   * @param string $body
   *   The comment body.
   * @param int $entity_id
   *   The entity ID the comment is attached to.
   * @param string $reference_type
   *   The entity type reference.
   * @param int $privacy
   *   The privacy level.
   * @param bool $in_stream
   *   Whether to add to the activity stream.
   *
   * @return int|string|null
   *   The new comment ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveComment($body, $entity_id, $reference_type, int $privacy = 0, bool $in_stream = TRUE) {
    // Prepare data.
    $gid = $this->route->getParameter('gid');
    $name = shortenString(strip_tags($body), 20);
    $mentions_url = NULL;
    // Custom set $mentions_url if this is a task.
    if ($reference_type == 'task') {
      $mentions_url = Url::fromRoute('ol_board.task_open_modal', ['gid' => $gid, 'task_id' => $entity_id])->toString();
    }
    // Send Notifications to @-mentioned users.
    $this->notifications->sendMentions($body, $mentions_url);
    // Save new comment.
    $ol_comment = OlComment::create(
          [
            'body' => $body,
            'name' => $name,
            'group_id' => $gid,
            'entity_id' => $entity_id,
            'entity_type' => $reference_type,
            'privacy' => $privacy,
          ]
      );
    $ol_comment->save();
    $id = $ol_comment->id();
    // Only create stream item if comment was not private.
    if ($privacy != 1 && $in_stream) {
      // Procedural use to avoid install profile dependency issues.
      $stream_body = shortenString(strip_tags($body), 200);
      $stream = \Drupal::service('olstream.stream');
      $stream->addStreamItem($gid, 'comment_added', $stream_body, 'comment', $id);
    }
    // Message.
    \Drupal::messenger()->addStatus(t('Your comment was added successfully.'));
    return $id;
  }

  /**
   * Update an existing comment.
   *
   * @param int $cid
   *   The comment ID.
   * @param string $body
   *   The new comment body.
   * @param int $privacy
   *   The privacy level.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateComment($cid, $body, $privacy) {
    // Send Notifications to @-mentioned users.
    $this->notifications->sendMentions($body);
    // Update comment with security check.
    if ($this->isCommentOwner($cid)) {
      // Load and save, update.
      $entity = OlComment::load($cid);
      $entity->set("body", $body);
      // Privacy = 0 means published.
      $entity->set("privacy", $privacy);
      $entity->set("name", strip_tags(shortenString($body, 40)));
      $entity->save();
      // Message.
      \Drupal::messenger()->addStatus(t('Your update was successful'));
    }
  }

  /**
   * Get the comment count for an entity.
   *
   * @param int $entity_id
   *   The entity ID.
   * @param string $entity_type
   *   The entity type.
   * @param int|null $gid
   *   The group ID.
   *
   * @return int
   *   The number of comments.
   */
  public function getCommentCount($entity_id, $entity_type, $gid = NULL) {
    // Get group id, if nog provided.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Query.
    $query = \Drupal::database()->select('ol_comment', 'comm');
    $query->addField('comm', 'id');
    $query->condition('comm.entity_id', $entity_id);
    $query->condition('comm.entity_type', $entity_type);
    $query->condition('comm.group_id', $gid);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Get and render comments for an entity.
   *
   * @param int $entity_id
   *   The entity ID.
   * @param string $entity_type
   *   The entity type.
   * @param string|null $order
   *   Sort order: 'asc' or 'desc'.
   * @param bool $show_small
   *   Whether to show small comment format.
   * @param bool $pager
   *   Whether to include pagination.
   *
   * @return string
   *   Rendered HTML for comments.
   *
   * @throws \Exception
   */
  public function getComments($entity_id, $entity_type, $order = NULL, $show_small = FALSE, $pager = TRUE) {
    // Only execute pager if needed, kinda nasty coded.
    if ($pager) {
      $page = \Drupal::service('pager.parameters')->findPage();
      $num_per_page = 20;
      $offset = $num_per_page * $page;
      $comments = $this->getCommentsByEntity($entity_id, $entity_type, $num_per_page, $offset, $order);
      $total_result = $this->getCommentsByEntity($entity_id, $entity_type, $num_per_page, $offset, $order, TRUE);
      $pager_manager = \Drupal::service('pager.manager');
      $pager = $pager_manager->createPager($total_result, $num_per_page);
      $pager->getCurrentPage();
    }
    else {
      $comments = $this->getCommentsByEntity($entity_id, $entity_type, NULL, NULL, $order);
    }
    // Build html.
    $comments_html = '';
    $edit_form_html = '';
    $current_uid = $this->members->getUserId();
    $group_admin_uid = \Drupal::service('olmembers.members')->isGroupAdmin($current_uid);
    // Loop though comments and build html.
    foreach ($comments as $comment) {
      // Private comments only visible to comment creator and content creator.
      if ($comment->privacy == 1 && $current_uid != $comment->user_id && $current_uid != $group_admin_uid) {
        continue;
      }
      $comment_row_data['privacy'] = $comment->privacy;
      $comment_row_data['body'] = $comment->body;
      $comment_row_data['body'] = ($show_small && $entity_type != 'task') ? nl2br(detectAndCreateLink($comment->body)) : $comment->body;
      $comment_row_data['username'] = $this->members->getUserName($comment->user_id);
      $comment_row_data['user_id'] = $comment->user_id;
      $comment_row_data['owner'] = $comment->user_id == $current_uid;
      $comment_row_data['comment_id'] = $comment->id;
      $comment_row_data['checked_class'] = ($comment->done) ? 'text-success' : '';
      $comment_row_data['body_hidden'] = ($comment->done) ? 'hidden' : '';
      $comment_row_data['created'] = time_elapsed_string('@' . $comment->created);
      $comment_row_data['user_picture'] = $this->members->getUserPictureUrl($comment->user_id);
      $comment_row_data['like_button'] = \Drupal::formBuilder()->getForm(LikeForm::class, 'comment', $comment->id);
      $comment_row_data['files'] = $this->files->getAttachedFiles('comment', $comment->id);
      if ($comment_row_data['owner'] == TRUE) {
        $comment_row_data['edit_form'] = \Drupal::formBuilder()->getForm(CommentForm::class, 'edit', $comment->id, $entity_type, $entity_id);
      }
      // Work around for now, in near future make inline editing
      // consistent for all small comments.
      if ($entity_type == 'task') {
        $comment_row_data['is_task_comment'] = TRUE;
      }
      // Render HTML.
      // Files lib needed for handling file attachments in comments.
      // Libraries renders multiple times, but only 1 css visible,
      // that's good. But not too much unneeded load?
      $template = ($show_small) ? 'comment_item_small' : 'comment_item';
      $render = [
        '#theme' => $template,
        '#attached' => [
          'library' => [
            'ol_main/ol_comments',
          ],
        ],
        '#vars' => $comment_row_data,
      ];
      $comments_html .= \Drupal::service('renderer')->render($render);
    }
    // Paged output.
    if ($pager) {
      $pager = [];
      $pager[] = ['#type' => 'pager'];
      $pager_html = \Drupal::service('renderer')->render($pager);
      return $comments_html . $pager_html;
      // Wow :)
    }
    else {
      // No pager output.
      return $comments_html;
    }
  }

  /**
   * Get comments for an entity from the database.
   *
   * @param int $entity_id
   *   The entity ID.
   * @param string $entity_type
   *   The entity type.
   * @param int|null $num_per_page
   *   Number of comments per page.
   * @param int|null $offset
   *   Offset for pagination.
   * @param string $order
   *   Sort order.
   * @param bool $get_total
   *   Whether to return total count.
   *
   * @return array|int
   *   Array of comment objects or total count.
   */
  private function getCommentsByEntity($entity_id, $entity_type, $num_per_page = NULL, $offset = NULL, $order = 'asc', $get_total = FALSE) {
    // Get comment detail data.
    $query = \Drupal::database()->select('ol_comment', 'comm');
    $query->addField('comm', 'id');
    $query->addField('comm', 'body');
    $query->addField('comm', 'user_id');
    $query->addField('comm', 'privacy');
    $query->addField('comm', 'group_id');
    $query->addField('comm', 'created');
    $query->addField('comm', 'done');
    $query->condition('comm.entity_id', $entity_id);
    $query->condition('comm.entity_type', $entity_type);
    $query->orderBy('comm.created', $order);
    // Data for message lists.
    if ($get_total == FALSE && $num_per_page && $offset) {
      $query->range($offset, $num_per_page);
      return $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == TRUE) {
      return $query->countQuery()->execute()->fetchField();
    }
    else {
      return $query->execute()->fetchAll();
    }
  }

  /**
   * Get comment data by comment ID.
   *
   * @param int $comment_id
   *   The comment ID.
   *
   * @return object
   *   The comment data object.
   */
  public function getCommentData($comment_id) {
    // Query for comment data.
    $query = \Drupal::database()->select('ol_comment', 'olc');
    $query->addField('olc', 'entity_type');
    $query->addField('olc', 'entity_id');
    $query->condition('olc.id', $comment_id);
    $query->condition('olc.status', 1);
    return $query->execute()->fetchObject();
  }

  /**
   * Check if the current user is the comment owner.
   *
   * @param int $cid
   *   The comment ID.
   *
   * @return bool
   *   TRUE if the current user is the owner.
   */
  private function isCommentOwner($cid) {
    $query = \Drupal::database()->select('ol_comment', 'com');
    $query->addField('com', 'user_id');
    $query->condition('com.id', $cid);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }

  /**
   * Toggle the done status of a comment.
   *
   * @param string $guuid
   *   The group UUID.
   * @param int $comment_id
   *   The comment ID.
   *
   * @return int
   *   The new done status.
   *
   * @throws \Exception
   */
  public function updateCommentDone($guuid, $comment_id) {
    // Get current group.
    $group = \Drupal::service('olmain.groups');
    $group_id = $group->getGroupIdByUuid($guuid);
    $user_in_group = $this->members->checkUserInGroup($group_id);
    $comment_in_group = $this->checkCommentInGroup($group_id, $comment_id);

    // Update with security checks.
    if ($user_in_group && $comment_in_group) {
      // Get current state.
      $current_state = $this->getCommentDoneStatus($comment_id);
      // Make new state opposite of current.
      if (empty($current_state)) {
        $new_state = 1;
      }
      elseif ($current_state == 1) {
        $new_state = 0;
      }
      // Update.
      \Drupal::database()->update('ol_comment')
        ->fields(['done' => $new_state])
        ->condition('id', $comment_id)
        ->condition('group_id', $group_id)
        ->execute();
    }
    // @todo , proper error handling, and setting default of $new_state.
    return $new_state;
  }

  /**
   * Check if a comment belongs to a group.
   *
   * @param int $group_id
   *   The group ID.
   * @param int $comment_id
   *   The comment ID.
   *
   * @return bool
   *   TRUE if the comment belongs to the group.
   */
  private function checkCommentInGroup($group_id, $comment_id) {
    $query = \Drupal::database()->select('ol_comment', 'comment');
    $query->addField('comment', 'id');
    $query->condition('comment.id', $comment_id);
    $query->condition('comment.group_id', $group_id);
    return (is_numeric($query->execute()->fetchField())) ? TRUE : FALSE;
  }

  /**
   * Get the done status of a comment.
   *
   * @param int $comment_id
   *   The comment ID.
   *
   * @return mixed
   *   The done status value.
   */
  private function getCommentDoneStatus($comment_id) {
    // Get data.
    $query = \Drupal::database()->select('ol_comment', 'comment');
    $query->addField('comment', 'done');
    $query->condition('comment.id', $comment_id);
    return $query->execute()->fetchField();
  }

}
