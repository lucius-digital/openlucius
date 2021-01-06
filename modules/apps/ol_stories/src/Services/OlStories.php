<?php

namespace Drupal\ol_stories\Services;

use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\ol_file\Entity\OlFile;
use Drupal\ol_story\Entity\OlStory;

/**
 * Class OlStories.
 */
class OlStories{

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
   * @param $route
   * @param $members
   * @param $stream
   * @param $mail
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
   * @return mixed
   */
  public function getStoriesList(){
    // Get story data.
    $query = \Drupal::database()->select('ol_story', 'story');
    $query->addField('story', 'id');
    $query->addField('story', 'body');
    $query->condition('story.status', 1);
    // Show stories one day.
    // $query->condition('story.created', time()-86400, '>' );
    $query->orderBy('story.created', 'desc');
    $query->join('users_field_data', 'ufd', 'ufd.uid = story.user_id');
    $query->addTag('ol_user_list');
    // Data for story list.
    return $query->execute()->fetchAll();
  }

  /**
   * @param $story_list_data
   *
   * @return string
   */
  public function renderStoriesList($story_list_data){

    // Initiate html.
    $stories_html = '';
    $stories_row_data['current_user_picture'] = $this->members->getUserPictureUrl();
    // Loop through array and render HTML rows via twig file.
    foreach ($story_list_data as $story){
      $story_data = $this->getStoryData($story->id);
      // Convert to clickable link.
      $stories_row_data['body'] = htmlspecialchars_decode($story_data->body);
      $stories_row_data['username'] = shortenString($story_data->username,8);
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
   * @param $story_id
   *
   * @return |null
   */
  private function getAttachedImageUrl($story_id){
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('file', 'uri');
    $query->condition('olf.entity_id', $story_id);
    $query->condition('olf.entity_type', 'story');
    $query->join('file_managed', 'file','file.fid = olf.file_id');
    $file_uri = $query->execute()->fetchField();
    // Return image styled url, if file is found.
    if ($file_uri) {
      $image_style = ImageStyle::load('post_image');
      return $image_style->buildUrl($file_uri);
    } else {
      return null;
    }
  }

  /**
   * @param $id
   * @return mixed
   */
  private function getStoryData($id){
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
   * @param $story_data
   *
   * @return mixed
   */
  public function getStoryTitle($story_data){
    $query = \Drupal::database()->select('ol_story', 'mess');
    $query->addField('mess', 'name');
    $query->condition('mess.id', $story_data[0]->id);
    return $query->execute()->fetchField();
  }

  /**
   * @param $name
   * @param $body
   * @param bool $send_mail
   *
   * @return int|string|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveStory($name, $body){
    // Save new story.
    $story = OlStory::create([
      'name' => $name,
      'body' =>  $body,
    ]);
    $story->save();
    $id = $story->id();
    // Message.
    \Drupal::messenger()->addStatus(t('Your story was posted successfully.'));
    // Return id.
    return $id;
  }

  /**
   * @param null $story_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeStoryAndFile($story_id){
    // Delete if story owner is true.
    if($this->isStoryOwner($story_id)) {
      // Delete file from hdd.
      $file_data = $this->getAttachedImageData($story_id);
      if(!empty($file_data->fid)) {
        $storage = \Drupal::entityTypeManager()->getStorage('file');
        $entities = $storage->loadMultiple([$file_data->fid]);
        $storage->delete($entities);
      }
      // Delete reference from dbase (and search index).
      if(!empty($file_data->fid)) {
        $file_ref_entity = OlFile::load($file_data->olf_id);
        $file_ref_entity->delete();
      }
      // Delete story from dbase (and search index).
      $story = OlStory::load($story_id);
      $story->delete();
      // Set message.
      \Drupal::messenger()->addStatus( t('Your story was successfully deleted.'));
    }
  }

  /**
   * @param $story_id
   *
   * @return mixed
   */
  private function getAttachedImageData($story_id){
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('file', 'fid');
    $query->addField('olf', 'id', 'olf_id');
    $query->condition('olf.entity_id', $story_id);
    $query->condition('olf.entity_type', 'story');
    $query->join('file_managed', 'file','file.fid = olf.file_id');
    return $query->execute()->fetchObject();

  }

  /**
   * @param $id
   * @return bool
   */
  private function isStoryOwner($id){
    $query = \Drupal::database()->select('ol_story', 'olm');
    $query->addField('olm', 'user_id');
    $query->condition('olm.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->members->getUserId());
  }

}
