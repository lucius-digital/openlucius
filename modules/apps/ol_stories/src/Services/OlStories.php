<?php

namespace Drupal\ol_stories\Services;

use Drupal\image\Entity\ImageStyle;
use Drupal\ol_file\Entity\OlFile;
use Drupal\ol_story\Entity\OlStory;

/**
 * Service for managing stories.
 */
class OlStories {

  /**
   * Current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
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
   * @var \Drupal\Core\Mail\MailSystemInterface
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
   * OlStories constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   Current route.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\ol_stream\Services\OlStream $stream
   *   Stream service.
   * @param \Drupal\Core\Mail\MailSystemInterface $mail
   *   Mail service.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   Groups service.
   * @param \Drupal\ol_main\Services\OlComments $comments
   *   Comments service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   Files service.
   */
  public function __construct($route, $members, $stream, $mail, $groups, $comments, $files) {
    $this->route = $route;
    $this->members = $members;
    $this->stream = $stream;
    $this->mail = $mail;
    $this->groups = $groups;
    $this->comments = $comments;
    $this->files = $files;
  }

  /**
   * Get the list of stories.
   *
   * @return array
   *   Array of story records.
   */
  public function getStoriesList() {
    // Get story data.
    $query = \Drupal::database()->select('ol_story', 'story');
    $query->addField('story', 'id');
    $query->addField('story', 'body');
    $query->condition('story.status', 1);
    // Show stories one day.
    $query->condition('story.created', time() - 86400, '>');
    $query->orderBy('story.created', 'desc');
    $query->join('users_field_data', 'ufd', 'ufd.uid = story.user_id');
    $query->addTag('ol_user_list');
    // Data for story list.
    return $query->execute()->fetchAll();
  }

  /**
   * Render stories list as HTML.
   *
   * @param array $story_list_data
   *   Array of story data.
   *
   * @return string
   *   Rendered HTML string.
   */
  public function renderStoriesList($story_list_data) {

    // Initiate html.
    $stories_html = '';
    $stories_row_data['current_user_picture'] = $this->members->getUserPictureUrl();
    // Loop through array and render HTML rows via twig file.
    foreach ($story_list_data as $story) {
      $story_data = $this->getStoryData($story->id);
      // Convert to clickable link.
      $stories_row_data['body'] = htmlspecialchars_decode($story_data->body);
      $stories_row_data['username'] = shortenString($story_data->username, 8);
      $stories_row_data['story_id'] = $story_data->id;
      $stories_row_data['user_id'] = $story_data->user_id;
      $stories_row_data['owner'] = $story_data->user_id == $this->members->getUserId();
      $stories_row_data['user_picture'] = $this->members->getUserPictureUrl($story_data->user_id);
      $stories_row_data['image_url'] = $this->getAttachedImageUrl($story_data->id);
      $render = ['#theme' => 'story_card', '#vars' => $stories_row_data];
      $stories_html .= \Drupal::service('renderer')->render($render);
    }
    return $stories_html;
  }

  /**
   * Get the attached image URL for a story.
   *
   * @param int $story_id
   *   Story ID.
   *
   * @return string|NULL
   *   Image URL or NULL if not found.
   */
  private function getAttachedImageUrl($story_id) {
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('file', 'uri');
    $query->condition('olf.entity_id', $story_id);
    $query->condition('olf.entity_type', 'story');
    $query->join('file_managed', 'file', 'file.fid = olf.file_id');
    $file_uri = $query->execute()->fetchField();
    // Return image styled url, if file is found.
    if ($file_uri) {
      $image_style = ImageStyle::load('post_image');
      return $image_style->buildUrl($file_uri);
    }
    else {
      return NULL;
    }
  }

  /**
   * Get story data by ID.
   *
   * @param int $id
   *   Story ID.
   *
   * @return object
   *   Story data object.
   */
  private function getStoryData($id) {
    // Get story detail data.
    $query = \Drupal::database()->select('ol_story', 'story');
    $query->addField('story', 'body');
    $query->addField('story', 'id');
    $query->addField('story', 'user_id');
    $query->addField('user', 'name', 'username');
    $query->condition('story.id', $id);
    $query->join('users_field_data', 'user', 'user.uid = story.user_id');
    $story_data = $query->execute()->fetchObject();
    return $story_data;
  }

  /**
   * Get the story title.
   *
   * @param array $story_data
   *   Story data array.
   *
   * @return string
   *   Story title.
   */
  public function getStoryTitle($story_data) {
    $query = \Drupal::database()->select('ol_story', 'mess');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $story_data[0]->id);
    return $query->execute()->fetchField();
  }

  /**
   * Save a new story.
   *
   * @param string $name
   *   Story name.
   * @param string $body
   *   Story body.
   *
   * @return int|string|null
   *   Story ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveStory($name, $body) {
    // Save new story.
    $story = OlStory::create([
      'name' => $name,
      'body' => $body,
    ]);
    $story->save();
    $id = $story->id();
    // Message.
    \Drupal::messenger()->addStatus(t('Your story was posted successfully.'));
    // Return id.
    return $id;
  }

  /**
   * Remove a story and its attached file.
   *
   * @param int $story_id
   *   Story ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeStoryAndFile($story_id) {
    // Delete if story owner is true.
    if ($this->isStoryOwner($story_id)) {
      // Delete file from hdd.
      $file_data = $this->getAttachedImageData($story_id);
      if (!empty($file_data->fid)) {
        $storage = \Drupal::entityTypeManager()->getStorage('file');
        $entities = $storage->loadMultiple([$file_data->fid]);
        $storage->delete($entities);
      }
      // Delete reference from dbase (and search index).
      if (!empty($file_data->fid)) {
        $file_ref_entity = OlFile::load($file_data->olf_id);
        $file_ref_entity->delete();
      }
      // Delete story from dbase (and search index).
      $story = OlStory::load($story_id);
      $story->delete();
      // Set message.
      \Drupal::messenger()->addStatus(t('Your story was successfully deleted.'));
    }
  }

  /**
   * Get attached image data for a story.
   *
   * @param int $story_id
   *   Story ID.
   *
   * @return object
   *   File data object.
   */
  private function getAttachedImageData($story_id) {
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('file', 'fid');
    $query->addField('olf', 'id', 'olf_id');
    $query->condition('olf.entity_id', $story_id);
    $query->condition('olf.entity_type', 'story');
    $query->join('file_managed', 'file', 'file.fid = olf.file_id');
    return $query->execute()->fetchObject();
  }

  /**
   * Check if current user is the story owner.
   *
   * @param int $id
   *   Story ID.
   *
   * @return bool
   *   True if current user is the owner.
   */
  private function isStoryOwner($id) {
    $query = \Drupal::database()->select('ol_story', 'olm');
    $query->addField('olm', 'user_id');
    $query->condition('olm.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }

}
