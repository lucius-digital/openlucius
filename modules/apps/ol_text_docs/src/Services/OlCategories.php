<?php

namespace Drupal\ol_text_docs\Services;

use Drupal\Core\Url;
use Drupal\ol_category\Entity\OlCategory;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Service for managing text document categories.
 */
class OlCategories {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * OlCategories constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   Current route match service.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\ol_stream\Services\OlStream $stream
   *   Stream service.
   */
  public function __construct($route, $connection, $messenger, $currentUser, $members, $stream) {
    $this->route = $route;
    $this->database = $connection;
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->members = $members;
    $this->stream = $stream;
  }

  /**
   * Save a new category.
   *
   * @param string $name
   *   The category name.
   *
   * @return int|string|null
   *   The new category ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveCategory($name) {
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
   * Remove a category.
   *
   * @param int|null $categoryId
   *   The category ID.
   */
  public function removeCategory($categoryId = NULL) {
    // Get current gid.
    $gid = $this->route->getParameter('gid');
    // Check if current user may remove category.
    if ($this->canAdminCategory($categoryId)) {
      // Get category id, if not provided.
      $categoryId = (empty($categoryId)) ? $this->route->getParameter('category_id') : $categoryId;
      // Get category name for stream item.
      $category_name = $this->getCategoryName($categoryId);
      // Delete category entity.
      \Drupal::database()->delete('ol_category')
        ->condition('id', $categoryId, '=')
        ->execute();
      // Delete category reference from text_docs.
      \Drupal::database()->update('ol_text_doc')
        ->fields(['category_id' => NULL])
        ->condition('category_id', $categoryId)
        ->execute();
      // Add stream item.
      $stream_body = $category_name;
      $this->stream->addStreamItem($gid, 'category_removed', $stream_body, 'category', $categoryId);
      \Drupal::messenger()->addStatus(t('Category removed successfully.'));
    }
    $path = Url::fromRoute('ol_text_docs.textdocs', ['gid' => $gid, 'category_id' => $categoryId])->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * Update a category name.
   *
   * @param string $name
   *   The new category name.
   * @param int|null $categoryId
   *   The category ID.
   */
  public function updateCategory($name, $categoryId = NULL) {
    // Check if current user may update category.
    if ($this->canAdminCategory($categoryId)) {
      \Drupal::database()->update('ol_category')
        ->fields(['name' => $name])
        ->condition('id', $categoryId)
        ->execute();
    }
  }

  /**
   * Get category data with counts.
   *
   * @param int|null $gid
   *   The group ID.
   *
   * @return array
   *   Array of category objects with count and admin flags.
   */
  public function getCategoriesData($gid = NULL) {
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $categories = $this->getCategories($gid);
    foreach ($categories as $category) {
      // Needed for badge.
      $category->count_files = $this->countTextdocsInCategory($category->id);
      // Needed to show/hide drop down.
      $category->can_admin = $this->canAdminCategory($category->id);
    }
    return $categories;
  }

  /**
   * Get categories for a group.
   *
   * @param int|null $gid
   *   The group ID.
   * @param bool|null $getTop
   *   Whether to get only the top category.
   *
   * @return array
   *   Array of category objects.
   */
  public function getCategories($gid = NULL, $getTop = NULL) {
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $query = \Drupal::database()->select('ol_category', 'olc');
    $query->addField('olc', 'id');
    $query->addField('olc', 'name');
    $query->condition('olc.group_id', $gid);
    $query->condition('olc.status', 1);
    if ($getTop) {
      $query->range(0, 1);
    }
    $query->orderBy('olc.name');
    return $query->execute()->fetchAll();
  }

  /**
   * Count text documents in a category.
   *
   * @param int $categoryId
   *   The category ID.
   *
   * @return int
   *   Number of text documents.
   */
  private function countTextdocsInCategory($categoryId) {
    $query = \Drupal::database()->select('ol_text_doc', 'ltd');
    $query->addField('ltd', 'category_id');
    $query->condition('ltd.category_id', $categoryId);
    $query->condition('ltd.status', 1);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Get categories as key-value pairs for form select.
   *
   * @param int|null $groupId
   *   The group ID.
   *
   * @return array
   *   Associative array of category ID to name.
   */
  public function getCategoriesInCurrentGroup($groupId = NULL) {
    $groupId = (empty($groupId)) ? $this->route->getParameter('gid') : $groupId;
    $current_categories = $this->getCategories($groupId);
    $categories = [];
    $categories[0] = t('-- Choose category --');
    foreach ($current_categories as $category) {
      $categories[$category->id] = $category->name;
    }
    return $categories;
  }

  /**
   * Check if current user can admin a category.
   *
   * @param int|null $categoryId
   *   The category ID.
   *
   * @return bool
   *   TRUE if user can admin the category.
   */
  private function canAdminCategory($categoryId = NULL) {
    // If user is group admin, return true.
    if (is_numeric($this->members->isGroupAdmin())) {
      return TRUE;
    }
    // Get category id, if not provided.
    $categoryId = (empty($categoryId)) ? $this->route->getParameter('category_id') : $categoryId;
    // User is not group admin, check if user is category owner.
    $query = \Drupal::database()->select('ol_category', 'of');
    $query->addField('of', 'user_id');
    $query->condition('of.id', $categoryId);
    $query->condition('of.status', 1);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->currentUser->id());
  }

  /**
   * Remove a text document from its category.
   *
   * @param int $id
   *   The text document ID.
   * @param int|null $groupId
   *   The group ID.
   */
  public function removeTextDocFromCategory($id, $groupId = NULL) {
    // Only remove if file owner or group admin.
    if ($this->members->isGroupAdmin() || $this->isTextDocOwner($id)) {
      $groupId = (empty($groupId)) ? $this->route->getParameter('gid') : $groupId;
      \Drupal::database()->update('ol_text_doc')
        ->fields(['category_id' => 0])
        ->condition('group_id', $groupId)
        ->condition('id', $id)
        ->execute();
      \Drupal::messenger()->addStatus(t('Your notebook was successfully removed from category.'));
    }
    else {
      \Drupal::messenger()->addWarning(t('No access.'));
    }
  }

  /**
   * Check if current user is the text document owner.
   *
   * @param int $id
   *   The text document ID.
   *
   * @return bool
   *   TRUE if user is the owner.
   */
  private function isTextDocOwner($id) {
    $query = \Drupal::database()->select('ol_text_doc', 'otd');
    $query->addField('otd', 'user_id');
    $query->condition('otd.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->currentUser->id());
  }

  /**
   * Get the category name by ID.
   *
   * @param int $categoryId
   *   The category ID.
   *
   * @return string
   *   The category name.
   */
  private function getCategoryName($categoryId) {
    $query = \Drupal::database()->select('ol_category', 'olf');
    $query->addField('olf', 'name');
    $query->condition('olf.id', $categoryId);
    return $query->execute()->fetchField();
  }

  /**
   * Place a text document in a category.
   *
   * @param int $categoryId
   *   The category ID.
   * @param int $id
   *   The text document ID.
   * @param int|null $gid
   *   The group ID.
   */
  public function placeTextDocInCategory($categoryId, $id, $gid = NULL) {
    // Get gid if empty.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Update file record with category id.
    \Drupal::database()->update('ol_text_doc')
      ->fields(['category_id' => $categoryId])
      ->condition('group_id', $gid)
      ->condition('id', $id)
      ->execute();
    // Message.
    \Drupal::messenger()->addStatus(t('Your notebook was successfully placed in category.'));
  }

}
