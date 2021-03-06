<?php

namespace Drupal\ol_main\Services;

use Drupal\Core\Url;
use Drupal\ol_comment\Entity\OlComment;

/**
 * Class OlComments.
 */
class OlComments{

  /**
   * @var $route
   */
  protected $route;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $notifications
   */
  protected $notifications;

  /**
   * @param $route
   * @param $members
   * @param $files
   * @param $notifications
   */
  public function __construct($route, $members, $files, $notifications) {
    $this->route = $route;
    $this->members = $members;
    $this->files = $files;
    $this->notifications = $notifications;
  }

  /**
   * Privacy: 0 = visible to all;
   *          1 = only visible to content creator;
   *          2 = invisible for externals.
   *
   * @param $body
   * @param $entity_id
   * @param $reference_type
   * @param int $privacy
   *
   * @param bool $in_stream
   *
   * @return int|string|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveComment($body, $entity_id, $reference_type, $privacy = 0, $in_stream = true){
    // Prepare data.
    $gid = $this->route->getParameter('gid');
    $name = shortenString(strip_tags($body),20);
    $mentions_url = null;
    // Custom set $mentions_url if this is a task.
    if ($reference_type == 'task'){
      $mentions_url = Url::fromRoute('ol_board.task_open_modal', ['gid' => $gid, 'task_id' => $entity_id])->toString();
    }
    // Send Notifications to @-mentioned users.
    $this->notifications->sendMentions($body, $mentions_url);
    // Save new comment.
    $ol_comment = OlComment::create([
      'body' =>  $body,
      'name' =>  $name,
      'group_id' => $gid,
      'entity_id' => $entity_id,
      'entity_type' => $reference_type,
      'privacy' => $privacy,
    ]);
    $ol_comment->save();
    $id = $ol_comment->id();
    // Only create stream item if comment was not private.
    if($privacy != 1 && $in_stream) {
      // We can't have this as dependency, else install profile will bitch during install.
      // So for now, procedural use of this service.
      $stream_body = shortenString(strip_tags($body), 200);
      $stream = \Drupal::service('olstream.stream');
      $stream->addStreamItem($gid, 'comment_added', $stream_body, 'comment', $id);
    }
    // Message.
    \Drupal::messenger()->addStatus(t('Your comment was added successfully.'));
    return $id;
  }

  /**
   * @param $cid
   * @param $body
   *
   * @param $privacy
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateComment($cid, $body, $privacy){
    // Send Notifications to @-mentioned users.
    $this->notifications->sendMentions($body);
    // Update comment with security check.
    if($this->isCommentOwner($cid)) {
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
   * @param $entity_id
   * @param $entity_type
   * @param null $gid
   *
   * @return mixed
   */
  public function getCommentCount($entity_id, $entity_type, $gid = null){
    // Get group id, if nog provided.
    $gid = (empty($gid)) ? $this->route->getParameter('gid'): $gid ;
    // Query.
    $query = \Drupal::database()->select('ol_comment', 'comm');
    $query->addField('comm', 'id');
    $query->condition('comm.entity_id', $entity_id);
    $query->condition('comm.entity_type', $entity_type);
    $query->condition('comm.group_id', $gid);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   *
   * @param $entity_id
   * @param $entity_type
   * @param null $order
   * @param bool $show_small
   * @param bool $pager
   *
   * @return string
   * @throws \Exception
   */
  public function getComments($entity_id, $entity_type, $order = null, $show_small = false, $pager = true){
    // Only execute pager if needed, kinda nasty coded.
    if($pager){
      $page = \Drupal::service('pager.parameters')->findPage();
      $num_per_page = 20;
      $offset = $num_per_page * $page;
      $comments = $this->getCommentsByEntity($entity_id, $entity_type, $num_per_page, $offset, $order);
      $total_result = $this->getCommentsByEntity($entity_id, $entity_type, $num_per_page, $offset, $order, TRUE);
      $pager_manager = \Drupal::service('pager.manager');
      $pager = $pager_manager->createPager($total_result, $num_per_page);
      $pager->getCurrentPage();
    } else {
      $comments = $this->getCommentsByEntity($entity_id, $entity_type, null, null, $order);
    }
    // Build html.
    $comments_html = '';
    $edit_form_html = '';
    $current_uid = $this->members->getUserId();
    $group_admin_uid = \Drupal::service('olmembers.members')->isGroupAdmin($current_uid);
    // Loop though comments and build html.
    foreach ($comments as $comment) {
      // Private comments only visible to comment creator and content creator.
      if ($comment->privacy == 1 && $current_uid != $comment->user_id && $current_uid != $group_admin_uid ){
        continue;
      }
      $comment_row_data['privacy'] = $comment->privacy;
      $comment_row_data['body'] = $comment->body;
      $comment_row_data['body'] = ($show_small && $entity_type != 'task') ? nl2br(detectAndCreateLink($comment->body)) : $comment->body;
      $comment_row_data['username'] = $this->members->getUserName($comment->user_id);
      $comment_row_data['user_id'] = $comment->user_id;
      $comment_row_data['owner'] = $comment->user_id == $current_uid;
      $comment_row_data['comment_id'] = $comment->id;
      $comment_row_data['created'] = time_elapsed_string('@'.$comment->created);
      $comment_row_data['user_picture'] = $this->members->getUserPictureUrl($comment->user_id);
      $comment_row_data['like_button'] = \Drupal::formBuilder()->getForm(\Drupal\ol_like\Form\LikeForm::class, 'comment', $comment->id);
      $comment_row_data['files'] = $this->files->getAttachedFiles('comment', $comment->id);
      if($comment_row_data['owner'] == TRUE){
        $comment_row_data['edit_form'] = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\CommentForm::class, 'edit', $comment->id, $entity_type, $entity_id);
      }
      // Work around for now, in near future make inline editing consistent for all small comments.
      if($entity_type == 'task'){
        $comment_row_data['is_task_comment'] = true;
      }
      // Render HTML.
      // Files lib needed for handling file attachments in comments.
      // Libraries renders multiple times, but only 1 css visible, that's good. But not too much unneeded load?
      $template = ($show_small) ? 'comment_item_small' : 'comment_item';
      $render = [
        '#theme' => $template,
        '#attached' => [
          'library' => [
            'ol_main/ol_comments',
          ]
        ],
        '#vars' => $comment_row_data,
      ];
      $comments_html .= \Drupal::service('renderer')->render($render);
    }
    // Paged output.
    if($pager){
      $pager = [];
      $pager[] = ['#type' => 'pager'];
      $pager_html = \Drupal::service('renderer')->render($pager);
      return $comments_html . $pager_html;
      // Wow :)
    } else {
      // No pager output.
      return $comments_html;
    }
  }

  /**
   * @param $entity_id
   * @param $entity_type
   * @param $num_per_page
   * @param $offset
   * @param string $order
   * @param bool $get_total
   *
   * @return mixed
   */
  private function getCommentsByEntity($entity_id, $entity_type, $num_per_page = null, $offset  = null, $order = 'asc', $get_total = false){
    // Get comment detail data.
    $query = \Drupal::database()->select('ol_comment', 'comm');
    $query->addField('comm', 'id');
    $query->addField('comm', 'body');
    $query->addField('comm', 'user_id');
    $query->addField('comm', 'privacy');
    $query->addField('comm', 'group_id');
    $query->addField('comm', 'created');
    $query->condition('comm.entity_id', $entity_id);
    $query->condition('comm.entity_type', $entity_type);
    $query->orderBy('comm.created', $order);
    // Data for message lists.
    if ($get_total == false && $num_per_page && $offset) {
      $query->range($offset, $num_per_page);
      return $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == true) {
      return $query->countQuery()->execute()->fetchField();
    }
    else {
      return $query->execute()->fetchAll();
    }
  }

  /**
   * @param $comment_id
   *
   * @return mixed
   */
  public function getCommentData($comment_id){
    // Query for me, b*tch
    $query = \Drupal::database()->select('ol_comment', 'olc');
    $query->addField('olc', 'entity_type');
    $query->addField('olc', 'entity_id');
    $query->condition('olc.id', $comment_id);
    $query->condition('olc.status', 1);
    return $query->execute()->fetchObject();
  }

  /**
   * @param $cid
   * @return bool
   */
  private function isCommentOwner($cid){
    $query = \Drupal::database()->select('ol_comment', 'com');
    $query->addField('com', 'user_id');
    $query->condition('com.id', $cid);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }


}
