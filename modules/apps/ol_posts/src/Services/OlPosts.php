<?php

namespace Drupal\ol_posts\Services;

use Drupal\Core\Url;
use Drupal\ol_like\Form\LikeForm;
use Drupal\ol_post\Entity\OlPost;
use Drupal\ol_post_settings\Entity\OlPostSettings;
use Drupal\ol_posts\Form\PostCommentForm;
use Drupal\ol_posts\Form\PostForm;

/**
 * Service for managing posts in the ol_posts module.
 */
class OlPosts {

  /**
   * Current route match.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGenerator
   */
  protected $route;

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Stream service.
   *
   * @var \Drupal\ol_stream\Services\OlStream
   */
  protected $stream;

  /**
   * Mail service.
   *
   * @var \Drupal\ol_mail\Services\OlMail
   */
  protected $mail;

  /**
   * Groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * Comments service.
   *
   * @var \Drupal\ol_main\Services\OlComments
   */
  protected $comments;

  /**
   * Files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * Notifications service.
   *
   * @var \Drupal\ol_notifications\Services\OlNotifications
   */
  protected $notifications;

  /**
   * OlPosts constructor.
   *
   * @param \Symfony\Component\Routing\Generator\UrlGenerator $route
   *   Current route match.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\ol_stream\Services\OlStream $stream
   *   Stream service.
   * @param \Drupal\ol_mail\Services\OlMail $mail
   *   Mail service.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   Groups service.
   * @param \Drupal\ol_main\Services\OlComments $comments
   *   Comments service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   Files service.
   * @param \Drupal\ol_notifications\Services\OlNotifications $notifications
   *   Notifications service.
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
   * Render a list of posts as HTML.
   *
   * @param array $post_list_data
   *   Array of post data objects.
   * @param string $view
   *   View mode: 'list' or 'page'.
   *
   * @return string
   *   Rendered HTML string for the posts list.
   *
   * @throws \Exception
   */
  public function renderPostsList($post_list_data, $view = 'list') {

    // Initiate html.
    $posts_html = '';
    $posts_row_data['current_user_picture'] = $this->members->getUserPictureUrl();
    // Loop through array and render HTML rows via twig file.
    foreach ($post_list_data as $post) {
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
      $posts_row_data['link'] = '/group/' . $post_data->group_id . '/posts/' . $post_data->id;
      if ($posts_row_data['owner']) {
        $posts_row_data['post_edit_form'] = \Drupal::formBuilder()->getForm(
          PostForm::class, 'edit', $post_data->id, NULL, $post_data->group_id
        );
      }
      $posts_row_data['comment_count'] = $this->comments->getCommentCount(
        $post_data->id, 'post', $post_data->group_id
      );
      $posts_row_data['comment_items'] = $this->comments->getComments(
        $post_data->id, 'post', 'asc', TRUE, FALSE
      );
      $posts_row_data['post_comment_form'] = \Drupal::formBuilder()->getForm(
        PostCommentForm::class, $post_data->id
      );
      $posts_row_data['files'] = $this->files->getAttachedFiles(
        'post', $post_data->id, 'post_image', NULL, $post_data->group_id
      );
      $posts_row_data['like_button'] = \Drupal::formBuilder()->getForm(
        LikeForm::class, 'post', $post_data->id
      );
      // Different template based on list or detail page.
      // $template = ($view == 'list') ? 'post_card_list' : 'post_card';
      $render = [
        '#theme' => 'post_card_list',
        '#vars' => $posts_row_data,
      ];
      $posts_html .= \Drupal::service('renderer')->render($render);
    }
    return $posts_html;
  }

  /**
   * Get a list of posts or total count.
   *
   * @param int|null $post_id
   *   Optional post ID to filter by.
   * @param int|null $num_per_page
   *   Number of posts per page.
   * @param int|null $offset
   *   Offset for pagination.
   * @param bool $get_total
   *   If TRUE, return total count instead of post data.
   * @param int|null $gid
   *   Optional group ID.
   *
   * @return array|int
   *   Array of post objects or total count.
   */
  public function getPostsList($post_id = NULL, $num_per_page = NULL, $offset = NULL, $get_total = FALSE, $gid = NULL) {
    // Handle group_id.
    $gid = (empty($gid)) ? $this->groups->getCurrentGroupId() : $gid;
    // Get post data.
    $query = \Drupal::database()->select('ol_post', 'pst');
    $query->addField('pst', 'id');
    $query->condition('pst.group_id', $gid);
    if (!empty($post_id)) {
      $query->condition('pst.id', $post_id);
    }
    $query->condition('pst.status', 1);
    $query->orderBy('pst.created', 'desc');
    // Data for post list.
    if ($get_total == FALSE) {
      $query->range($offset, $num_per_page);
      $post_data = $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == TRUE) {
      $post_data = $query->countQuery()->execute()->fetchField();
    }
    return $post_data;
  }

  /**
   * Retrieve post detail data by ID.
   *
   * @param int $id
   *   The post ID.
   *
   * @return object
   *   The post data object.
   */
  private function getPostData($id) {
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
   * Get the title of a post from its data.
   *
   * @param array $post_data
   *   Array of post data objects.
   *
   * @return string
   *   The post title.
   */
  public function getPostTitle($post_data) {
    $query = \Drupal::database()->select('ol_post', 'mess');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $post_data[0]->id);
    return $query->execute()->fetchField();
  }

  /**
   * Save a new post.
   *
   * @param string $name
   *   The post name/title.
   * @param string $body
   *   The post body content.
   * @param bool $send_mail
   *   Whether to send email notifications.
   * @param int|null $gid
   *   Optional group ID.
   * @param bool $add_stream
   *   Whether to add a stream item.
   * @param bool $global_post
   *   Whether this is a global homepage post.
   *
   * @return int|string|null
   *   The new post ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function savePost($name, $body, $send_mail = FALSE, $gid = NULL, $add_stream = TRUE, $global_post = FALSE) {
    // Get group id.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Save new post.
    $post = OlPost::create([
      'name' => $name,
      'body' => $body,
      'group_id' => $gid,
    ]);
    $post->save();
    $id = $post->id();
    // Send Notifications to @-mentioned users.
    $this->notifications->sendMentions($body);
    // Add stream item.
    if ($add_stream) {
      // Create new stream item.
      $stream_body = strip_tags($name);
      // Create stream item.
      $this->stream->addStreamItem($gid, 'post_added', $stream_body, 'posts', $id);
    }
    // Mail if true.
    if ($send_mail == TRUE) {
      // Generate url and send mails.
      // Switch url based on global homepage post, or group post.
      $url = $this->getGlobalOrGroupUrl($global_post, $gid);
      $intro = t('A new post was added on the front page:');
      $this->mail->sendMail($name, $url, $intro, NULL, NULL, $gid, NULL, NULL, strip_tags($body));
    }
    // Post.
    \Drupal::messenger()->addStatus(t('Your post was added successfully.'));
    // Return id.
    return $id;
  }

  /**
   * Update an existing post.
   *
   * @param int $id
   *   The post ID.
   * @param string $name
   *   The post name/title.
   * @param string $body
   *   The post body content.
   * @param bool $send_mail
   *   Whether to send email notifications.
   * @param bool $global_post
   *   Whether this is a global homepage post.
   * @param int|null $gid
   *   Optional group ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updatePost($id, $name, $body, $send_mail = FALSE, $global_post = FALSE, $gid = NULL) {
    // Get group id.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Update post with spoofing protection.
    if ($this->isPostOwner($id)) {
      $entity = OlPost::load($id);
      $entity->set("name", $name);
      $entity->set("body", $body);
      $entity->save();
      // Send Notifications to @-mentioned users.
      $this->notifications->sendMentions($body);
      // Mail if checked by user.
      if ($send_mail == TRUE) {
        // Generate url and send mails.
        $url = $this->getGlobalOrGroupUrl($global_post, $gid);
        $this->mail->sendMail($name, $url);
      }
      \Drupal::messenger()->addStatus(t('Your post was updated successfully.'));
    }
  }

  /**
   * Generate URL for global or group posts.
   *
   * @param bool $global_post
   *   Whether this is a global homepage post.
   * @param int $gid
   *   The group ID.
   *
   * @return string
   *   The generated URL string.
   */
  private function getGlobalOrGroupUrl($global_post, $gid) {
    // Generate url and send mails.
    // Switch url based on global homepage post, or group post.
    if ($global_post) {
      $url = Url::fromRoute('ol_main.home')->toString();
    }
    else {
      $url = Url::fromRoute('lus_post.posts', ['gid' => $gid])->toString();
    }
    return $url;
  }

  /**
   * Check if the current user is the owner of a post.
   *
   * @param int $id
   *   The post ID.
   *
   * @return bool
   *   TRUE if the current user is the post owner.
   */
  private function isPostOwner($id) {
    $query = \Drupal::database()->select('ol_post', 'olm');
    $query->addField('olm', 'user_id');
    $query->condition('olm.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }

  /**
   * Save new post settings.
   *
   * @param string $question
   *   The question to ask.
   * @param string $send_days_json
   *   JSON-encoded array of send days.
   * @param int $enabled
   *   Whether the settings are enabled.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function savePostSettings($question, $send_days_json, $enabled) {
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
   * Get the current post settings.
   *
   * @return object
   *   The post settings object.
   */
  public function getPostSettings() {
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
   * Update existing post settings.
   *
   * @param int $id
   *   The settings ID.
   * @param string $question
   *   The question to ask.
   * @param string $send_days_json
   *   JSON-encoded array of send days.
   * @param int $enabled
   *   Whether the settings are enabled.
   */
  public function updatePostSettings($id, $question, $send_days_json, $enabled) {
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
