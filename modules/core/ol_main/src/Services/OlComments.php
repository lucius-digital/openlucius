<?php

namespace Drupal\ol_main\Services;

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
   * @param $route
   * @param $members
   * @param $files
   */
  public function __construct($route, $members, $files) {
    $this->route = $route;
    $this->members = $members;
    $this->files =  $files;
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
   * @return int|string|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveComment($body, $entity_id, $reference_type, $privacy = 0){
    // Prepare data.
    $gid = $this->route->getParameter('gid');
    $name = strip_tags(shortenString($body,50));
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
    // Add stream item.
    $stream_body = t('Added a new comment: @comment', array('@comment' => $name)); // Create new stream item.
    // We can't have this as dependency, else install profile will bitch during install.
    // So for now, procedural use of this service.
    $stream = \Drupal::service('olstream.stream');
    $stream->addStreamItem($gid, 'comment_added', $stream_body, 'comment', $id); // Create stream item.
    // Message.
    \Drupal::messenger()->addStatus(t('Your comment was added successfully.'));
    // Maybe implement: https://www.drupal.org/docs/8/modules/webform/webform-cookbook/how-to-provide-anchor-link-submit-for-page-wizard
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
    // Update comment with spoofing protection.
    if($this->isCommentOwner($cid)) {
      // Load and save, update.
      $entity = OlComment::load($cid);
      $entity->set("body", $body);
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
   * @param $entity_id
   * @param $entity_type
   * @param null $order
   *
   * @return string
   * @throws \Exception
   */
  public function getComments($entity_id, $entity_type, $order = null){
    // Pager initialize.
    $page = \Drupal::service('pager.parameters')->findPage();
    $num_per_page = 20;
    $offset = $num_per_page * $page;
    // Get comments detail data.
    $comments = $this->getCommentsByEntity($entity_id, $entity_type, $num_per_page, $offset, $order);
    // Pager, now that we have the total number of results, .
    $total_result = $this->getCommentsByEntity($entity_id, $entity_type, $num_per_page, $offset, $order, true);
    $pager_manager = \Drupal::service('pager.manager');
    $pager = $pager_manager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();
    // Build html.
    $comments_html = '';
    $current_uid = $this->members->getUserId();
    $group_admin_uid = \Drupal::service('olmembers.members')->isGroupAdmin($current_uid);
    foreach ($comments as $comment){

      // Private comments only visible to comment creator and content creator.
      if ($comment->privacy == 1 && $current_uid != $comment->user_id && $current_uid != $group_admin_uid ){
        continue;
      }
      $comment_row_data['privacy'] = $comment->privacy;
      $comment_row_data['body'] = $comment->body;
      $comment_row_data['username'] = $this->members->getUserName($comment->user_id);
      $comment_row_data['user_id'] = $comment->user_id;
      $comment_row_data['owner'] = $comment->user_id == $current_uid;
      $comment_row_data['comment_id'] = $comment->id;
      $comment_row_data['created'] = time_elapsed_string('@'.$comment->created);
      $comment_row_data['user_picture'] = $this->members->getUserPictureUrl($comment->user_id);
      if($comment_row_data['owner'] == TRUE) {
        $comment_row_data['edit_form'] = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\CommentForm::class, 'edit', $comment->id, $entity_type, $entity_id);
      }
      $comment_row_data['files'] = $this->files->getAttachedFiles('comment', $comment->id);
      // Render HTML.
      // Files lib needed for handling file attachments in comments.
      // Libraries renders multiple times, but only 1 css visible, that's good. But not too much unneeded load?
      $render = [
        '#theme' => 'comment_item',
        '#attached' => ['library' => ['ol_main/ol_comments','ol_files/ol_files']],
        '#vars' => $comment_row_data,
      ];
      $comments_html .= \Drupal::service('renderer')->render($render);
    }
    // Build render array.
    $pager = [];
    $pager[] = ['#type' => 'pager'];
    $pager_html = \Drupal::service('renderer')->render($pager);
    return $comments_html.$pager_html;
    // Wow :)
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
  private function getCommentsByEntity($entity_id, $entity_type, $num_per_page, $offset, $order = 'asc', $get_total = false){
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
    if ($get_total == false) {
      $query->range($offset, $num_per_page);
      return $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == true) {
      return $query->countQuery()->execute()->fetchField();
    }
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
