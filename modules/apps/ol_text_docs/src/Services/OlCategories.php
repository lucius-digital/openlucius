<?php

namespace Drupal\ol_text_docs\Services;

use Drupal\Core\Url;
use Drupal\ol_category\Entity\OlCategory;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class OlCategories.
 */
class OlCategories{

  /**
  * @var $database
  */
  protected $database;

  /**
   * @var $route
   */
  protected $route;

  /**
   * @var $messenger
   */
  protected $messenger;

  /**
   * @var $current_user
   */
  protected $current_user;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $stream
   */
  protected $stream;

  /**
   * OlCategory constructor.
   *
   * @param $route
   * @param $connection
   * @param $messenger
   * @param $current_user
   * @param $members
   * @param $files
   */
  public function __construct($route, $connection, $messenger, $current_user, $members, $stream) {
    $this->route = $route;
    $this->database = $connection;
    $this->messenger = $messenger;
    $this->current_user = $current_user;
    $this->members = $members;
    $this->stream = $stream;
  }

  /**
   * @param $name
   * @return int|string|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveCategory($name){
    $gid = $this->route->getParameter('gid');
    $category = OlCategory::create([
      'name' => $name,
      'group_id' => $gid,
    ]);
    $category->save();
    $id = $category->id();
    $stream_body = $name;
    $this->stream->addStreamItem($gid, 'category_added', $stream_body, 'category', $id);
    return $id;
  }

  /**
   * @param null $category_id
   */
  public function removeCategory($category_id = null){
    // Get current gid.
    $gid = $this->route->getParameter('gid');
    // Check if current user may remove category.
    if($this->canAdminCategory($category_id)) {
      // Get category id, if not provided.
      $category_id = (empty($category_id)) ? $this->route->getParameter('category_id') : $category_id;
      // Get category name for stream item.
      $category_name = $this->getCategoryName($category_id);
      // Delete category entity.
      \Drupal::database()->delete('ol_category')
        ->condition('id', $category_id, '=')
        ->execute();
      // Delete category reference from text_docs.
      \Drupal::database()->update('ol_text_doc')
        ->fields(['category_id' => null])
        ->condition('category_id', $category_id)
        ->execute();
      // Add stream item.
      $stream_body = $category_name;
      $this->stream->addStreamItem($gid, 'category_removed', $stream_body, 'category', $category_id);
      \Drupal::messenger()->addStatus(t('Category removed successfully.'));
    }
    $path = Url::fromRoute('ol_text_docs.textdocs',['gid' => $gid, 'category_id' => $category_id])->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * @param $name
   * @param null $category_id
   */
  public function updateCategory($name, $category_id = null){
    // Check if current user may remove category.
    if($this->canAdminCategory($category_id)) {
      \Drupal::database()->update('ol_category')
        ->fields(['name' => $name])
        ->condition('id', $category_id)
        ->execute();
    }
  }

  /**
   * @param $gid
   * @return mixed
   */
  function getCategoriesData($gid = null){
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $categories = $this->getCategories($gid);
    foreach ($categories as $category){
      // Needed for badge.
      $category->count_files = $this->countTextdocsInCategory($category->id);
      // Needed to show/hide drop down.
      $category->can_admin = $this->canAdminCategory($category->id);
    }
    return $categories;
  }

  /**
   * @param null $gid
   * @param null $get_top
   *
   * @return mixed
   */
  public function getCategories($gid = null, $get_top = null){
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $query = \Drupal::database()->select('ol_category', 'olc');
    $query->addField('olc', 'id');
    $query->addField('olc', 'name');
    $query->condition('olc.group_id', $gid);
    $query->condition('olc.status', 1);
    if ($get_top) {
      $query->range(0, 1);
    }
    $query->orderBy('olc.name');
    return $query->execute()->fetchAll();
  }

  /**
   * @param $category_id
   * @return mixed
   */
  private function countTextdocsInCategory($category_id){
    $query = \Drupal::database()->select('ol_text_doc', 'ltd');
    $query->addField('ltd', 'category_id');
    $query->condition('ltd.category_id', $category_id);
    $query->condition('ltd.status', 1);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * @param $group_id
   * @return array
   */
  function getCategoriesInCurrentGroup($group_id = null){
    $group_id = (empty($group_id)) ? $this->route->getParameter('gid') : $group_id;
    $current_categories = $this->getCategories($group_id);
    $categories = array();
    $categories[0] = '--'. t('Choose category') .'--';
    foreach ($current_categories as $category){
      $categories[$category->id] = $category->name;
    }
    return $categories;
  }

  /**
   * @param $category_id
   * @return bool
   */
  private function canAdminCategory($category_id = null){
    // If user is group admin, return true.
    if(is_numeric($this->members->isGroupAdmin())) {
      return TRUE;
    }
    // Get category id, if not provided.
    $category_id = (empty($category_id)) ? $this->route->getParameter('category_id') : $category_id;
    // User is not group admin of current group, check if user is category owner.
    $query = \Drupal::database()->select('ol_category', 'of');
    $query->addField('of', 'user_id');
    $query->condition('of.id', $category_id);
    $query->condition('of.status', 1);
    $uid =  $query->execute()->fetchField();
    return ($uid == $this->current_user->id());
  }

  /**
   * @param $file_id
   * @param null $group_id
   */
  function removeTextDocFromCategory($id, $group_id = null){
    // Only remove if file owner or group admin
    if($this->members->isGroupAdmin() || $this->isTextDocOwner($id) ){
      $group_id = (empty($group_id)) ? $this->route->getParameter('gid') : $group_id;
      \Drupal::database()->update('ol_text_doc')
        ->fields(['category_id' => 0])
        ->condition('group_id', $group_id)
        ->condition('id', $id)
        ->execute();
      \Drupal::messenger()->addStatus(t('Your notebook was successfully removed from category.'));
    }
    else{
      \Drupal::messenger()->addWarning(t('No access.'));
    }
  }

  /**
   * This is duplicate, see files service, to avoid Circular reference.
   * @param $fid
   * @return bool
   */
  private function isTextDocOwner($id){
    $query = \Drupal::database()->select('ol_text_doc', 'otd');
    $query->addField('otd', 'user_id');
    $query->condition('otd.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->current_user->id());
  }

  /**
   * @param $category_id
   * @return mixed
   */
  private function getCategoryName($category_id){
    $query = \Drupal::database()->select('ol_category', 'olf');
    $query->addField('olf', 'name');
    $query->condition('olf.id', $category_id);
    return $query->execute()->fetchField();
  }

  /**
   * @param $id_category
   * @param $fid
   * @param null $gid
   */
  public function placeTextDocInCategory($category_id, $id, $gid = null){
    // Get gid if empty.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Update file record with category id.
    \Drupal::database()->update('ol_text_doc')
      ->fields(['category_id' => $category_id])
      ->condition('group_id', $gid)
      ->condition('id', $id)
      ->execute();
    // Message.
    \Drupal::messenger()->addStatus(t('Your notebook was successfully placed in category.'));
  }

}
