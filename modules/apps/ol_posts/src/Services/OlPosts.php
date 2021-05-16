<?php

namespace Drupal\ol_posts\Services;

use Drupal\Core\Url;
use Drupal\ol_icebreaker_settings\Entity\OlIcebreakerSettings;
use Drupal\ol_post\Entity\OlPost;
use Drupal\ol_post_settings\Entity\OlPostSettings;

/**
 * Class OlPosts.
 */
class OlPosts{

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
   * @var $notifications
   */
  protected $notifications;

  /**
   * @param $route
   * @param $members
   * @param $stream
   * @param $mail
   * @param $groups
   * @param $comments
   * @param $files
   * @param $notifications
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
   * @param $post_list_data
   * @param string $view
   *
   * @return string
   * @throws \Exception
   */
  public function renderPostsList($post_list_data, $view = 'list'){

    // Initiate html.
    $posts_html = '';
    $posts_row_data['current_user_picture'] = $this->members->getUserPictureUrl();
    // Loop through array and render HTML rows via twig file.
    foreach ($post_list_data as $post){
      $post_data = $this->getPostData($post->id);
      // Convert to clickable link.
      $body = detectAndCreateLink($post_data->body);
      $posts_row_data['body'] = nl2br($body);
      $posts_row_data['name'] = $post_data->name;
      $posts_row_data['created'] = $post_data->created;
      $posts_row_data['username'] = $post_data->username;
      $posts_row_data['id'] = $post_data->id;
      $posts_row_data['id_group'] = $post_data->group_id;
      $posts_row_data['user_id'] = $post_data->user_id;
      $posts_row_data['owner'] = $post_data->user_id == $this->members->getUserId();
      $posts_row_data['view'] = $view;
      $posts_row_data['user_picture'] = $this->members->getUserPictureUrl($post_data->user_id);
      $posts_row_data['link'] = '/group/'.$post_data->group_id.'/posts/'.$post_data->id;
      if($posts_row_data['owner'] == TRUE) {
        $posts_row_data['post_edit_form'] = \Drupal::formBuilder()->getForm(\Drupal\ol_posts\Form\PostForm::class, 'edit', $post_data->id);
      }
      $posts_row_data['comment_count'] = $this->comments->getCommentCount($post_data->id, 'post', $post_data->group_id);
      $posts_row_data['comment_items'] = $this->comments->getComments($post_data->id, 'post', 'asc', true, false);
      $posts_row_data['post_comment_form'] = \Drupal::formBuilder()->getForm(\Drupal\ol_posts\Form\PostCommentForm::class, $post_data->id);
      $posts_row_data['files'] = $this->files->getAttachedFiles('post', $post_data->id, 'post_image',null,$post_data->group_id);
      $posts_row_data['like_button'] = \Drupal::formBuilder()->getForm(\Drupal\ol_like\Form\LikeForm::class, 'post', $post_data->id);
      // Different template based on list or detail page.
      //$template = ($view == 'list') ? 'post_card_list' : 'post_card';
      $render = [
        '#theme' => 'post_card_list',
        '#vars' => $posts_row_data
      ];
      $posts_html .= \Drupal::service('renderer')->render($render);
    }
    return $posts_html;
  }

  /**
   * @param null $post_id
   *
   * @param null $num_per_page
   * @param null $offset
   * @param bool $get_total
   *
   * @return mixed
   */
  public function getPostsList($post_id = null, $num_per_page = null, $offset = null , $get_total = false, $gid = null){
    // Handle group_id.
    $gid = (empty($gid)) ? $this->groups->getCurrentGroupId() : $gid;
    // Get post data.
    $query = \Drupal::database()->select('ol_post', 'pst');
    $query->addField('pst', 'id');
    $query->condition('pst.group_id', $gid);
    if(!empty($post_id)) {
      $query->condition('pst.id', $post_id);
    }
    $query->condition('pst.status', 1);
    $query->orderBy('pst.created', 'desc');
    // Data for post list.
    if ($get_total == false) {
      $query->range($offset, $num_per_page);
      $post_data = $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == true) {
      $post_data = $query->countQuery()->execute()->fetchField();
    }
    return $post_data;
  }

  /**
   * @param $id
   * @return mixed
   */
  private function getPostData($id){
    // Get post detail data.
    $query = \Drupal::database()->select('ol_post', 'mess');
    $query->addField('mess', 'body');
    $query->addField('mess', 'group_id');
    $query->addField('mess', 'id');
    $query->addField('mess', 'name');
    $query->addField('mess', 'created');
    $query->addField('mess', 'user_id');
    $query->addField('mess', 'status');
    $query->addField('user', 'name', 'username');
    $query->condition('mess.id', $id);
    $query->join('users_field_data', 'user', 'user.uid = mess.user_id');
    $post_data = $query->execute()->fetchObject();
    return $post_data;
  }

  /**
   * @param $post_data
   *
   * @return mixed
   */
  public function getPostTitle($post_data){
    $query = \Drupal::database()->select('ol_post', 'mess');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $post_data[0]->id);
    return $query->execute()->fetchField();
  }

  /**
   * @param $name
   * @param $body
   * @param bool $send_mail
   *
   * @param null $gid
   * @param bool $add_stream
   *
   * @param bool $global_post
   *
   * @return int|string|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function savePost($name, $body, $send_mail = false, $gid = null, $add_stream = true, $global_post = false){
    // Get group id.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Save new post.
    $post = OlPost::create([
      'name' => $name,
      'body' =>  $body,
      'group_id' => $gid
    ]);
    $post->save();
    $id = $post->id();
    // Send Notifications to @-mentioned users.
    $this->notifications->sendMentions($body);
    // Add stream item.
    if($add_stream) {
      $stream_body = strip_tags($name); // Create new stream item.
      $this->stream->addStreamItem($gid, 'post_added', $stream_body, 'posts', $id); // Create stream item.
    }
    // Mail if true
    if ($send_mail == true){
      // Generate url and send mails.
      // Switch url based on global homepage post, or group post.
      $url = $this->getGlobalOrGroupUrl($global_post, $gid);
      $intro = t('A new post was added on the front page:');
      $this->mail->sendMail($name, $url, $intro, null, null, $gid, null, null, strip_tags($body));
    }
    // Post.
    \Drupal::messenger()->addStatus(t('Your post was added successfully.'));
    // Return id
    return $id;
  }

  /**
   * @param $id
   * @param $name
   * @param $body
   *
   * @param bool $send_mail
   * @param bool $global_post
   *
   * @param null $gid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updatePost($id, $name, $body, $send_mail = false, $global_post = false, $gid = null){
    // Get group id.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Update post  with spoofing protection.
    if($this->isPostOwner($id)) {
      $entity = OlPost::load($id);
      $entity->set("name", $name);
      $entity->set("body", $body);
      $entity->save();
      // Send Notifications to @-mentioned users.
      $this->notifications->sendMentions($body);
      // Mail if checked by user.
      if($send_mail == true){
        // Generate url and send mails.
        $url = $this->getGlobalOrGroupUrl($global_post, $gid);
        $this->mail->sendMail($name, $url);
      }
      \Drupal::messenger()->addStatus(t('Your post was updated successfully.'));
    }
  }

  /**
   * @param $global_post
   * @param $gid
   *
   * @return \Drupal\Core\GeneratedUrl|string
   */
  private function getGlobalOrGroupUrl($global_post, $gid){
    // Generate url and send mails.
    // Switch url based on global homepage post, or group post.
    if ($global_post) {
      $url = Url::fromRoute('ol_main.home')->toString();
    } else {
      $url = Url::fromRoute('lus_post.posts', ['gid' => $gid])->toString();
    }
    return $url;
  }


  /**
   * @param $id
   * @return bool
   */
  private function isPostOwner($id){
    $query = \Drupal::database()->select('ol_post', 'olm');
    $query->addField('olm', 'user_id');
    $query->condition('olm.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }

  /**
   * @param $question
   * @param $send_days_json
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function savePostSettings($question, $send_days_json, $enabled){
    // Get current group id.
    $gid = $this->route->getParameter('gid');
    // Save new icebreaker settings.
    $next_execution = strtotime('today midnight');
    $ol_icebreakers = OlPostSettings::create([
      'group_id' => $gid,
      'question' => $question,
      'next_execution' => $next_execution,
      'send_days' => $send_days_json,
      'status' => $enabled,
    ]);
    $ol_icebreakers->save();
    // Add message.
    \Drupal::messenger()->addStatus(t('Your settings are saved successfully.'));
  }

  /**
   * @return mixed
   */
  public function getPostSettings(){
    $gid = $this->route->getParameter('gid');
    $query = \Drupal::database()->select('ol_post_settings', 'ops');
    $query->addField('ops', 'id');
    $query->addField('ops', 'question');
    $query->addField('ops', 'send_days');
    $query->addField('ops', 'status');
    $query->condition('ops.group_id', $gid);
    return $query->execute()->fetchObject();
  }

  /**
   * @param $id
   * @param $question
   * @param $send_days_json
   * @param $enabled
   */
  public function updatePostSettings($id, $question, $send_days_json, $enabled){
    $uid = \Drupal::currentUser()->id();
    $gid = $this->route->getParameter('gid');
    \Drupal::database()->update('ol_post_settings')
      ->fields([
        'question' => $question,
        'send_days' => $send_days_json,
        'status' => $enabled,
      ])
      ->condition('id', $id)
      ->condition('user_id', $uid)
      ->condition('group_id', $gid)
      ->execute();
    \Drupal::messenger()->addStatus(t('Your settings are saved successfully.'));
  }


}
