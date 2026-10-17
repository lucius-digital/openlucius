<?php

namespace Drupal\ol_files\Services;

use Drupal\Core\Url;
use Drupal\ol_folder\Entity\OlFolder;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Service for managing folders.
 */
class OlFolders {

  /**
   * Database connection.
   *
   * @var mixed
   */
  protected $database;

  /**
   * Current route.
   *
   * @var mixed
   */
  protected $route;

  /**
   * Messenger service.
   *
   * @var mixed
   */
  protected $messenger;

  /**
   * Current user.
   *
   * @var mixed
   */
  protected $currentUser;

  /**
   * Members service.
   *
   * @var mixed
   */
  protected $members;

  /**
   * OlFolders constructor.
   *
   * @param mixed $route
   *   Current route.
   * @param mixed $connection
   *   Database connection.
   * @param mixed $messenger
   *   Messenger service.
   * @param mixed $currentUser
   *   Current user.
   * @param mixed $members
   *   Members service.
   */
  public function __construct($route, $connection, $messenger, $currentUser, $members) {
    $this->route = $route;
    $this->database = $connection;
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->members = $members;
  }

  /**
   * Save a new folder.
   *
   * @param string $name
   *   Folder name.
   *
   * @return int|string|null
   *   The new folder ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveFolder($name) {
    $gid = $this->route->getParameter('gid');
    $folder = OlFolder::create([
      'name' => $name,
      'group_id' => $gid,
    ]);
    $folder->save();
    $id = $folder->id();
    $stream = \Drupal::service('olstream.stream');
    $stream->addStreamItem($gid, 'folder_added', $name, 'folder', $id);
    return $id;
  }

  /**
   * Remove a folder.
   *
   * @param int|null $folderId
   *   The folder ID.
   */
  public function removeFolder($folderId = NULL) {
    // Get current gid.
    $gid = $this->route->getParameter('gid');
    // Check if current user may remove folder.
    if ($this->canAdminFolder($folderId)) {
      // Get folder id, if not provided.
      $folderId = (empty($folderId)) ? $this->route->getParameter('folder_id') : $folderId;
      // Get folder name for stream item.
      $folderName = $this->getFolderName($folderId);
      // Delete folder entity.
      \Drupal::database()->delete('ol_folder')
        ->condition('id', $folderId, '=')
        ->execute();
      // Delete folder reference from files.
      \Drupal::database()->update('ol_file')
        ->fields(['folder_id' => NULL])
        ->condition('folder_id', $folderId)
        ->execute();
      // Add stream item.
      $streamBody = $folderName;
      $stream = \Drupal::service('olstream.stream');
      $stream->addStreamItem($gid, 'folder_removed', $streamBody, 'folder', $folderId);
      \Drupal::messenger()->addStatus(t('Folder removed successfully.'));
    }
    $path = Url::fromRoute('ol_files.group_files', ['gid' => $gid, 'folder_id' => $folderId])->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * Update a folder name.
   *
   * @param string $name
   *   New folder name.
   * @param int|null $folderId
   *   The folder ID.
   */
  public function updateFolder($name, $folderId = NULL) {
    // Check if current user may update folder.
    if ($this->canAdminFolder($folderId)) {
      \Drupal::database()->update('ol_folder')
        ->fields(['name' => $name])
        ->condition('id', $folderId)
        ->execute();
    }
  }

  /**
   * Get folder data with file counts.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return mixed
   *   Array of folder objects.
   */
  public function getFoldersData($gid) {
    $folders = $this->getFolders($gid);
    foreach ($folders as $folder) {
      // Needed for badge.
      $folder->count_files = $this->countFilesInFolder($folder->id);
      // Needed to show/hide drop down.
      $folder->can_admin = $this->canAdminFolder($folder->id);
    }
    return $folders;
  }

  /**
   * Get folders for a group.
   *
   * @param int|null $gid
   *   The group ID.
   * @param bool|null $getTop
   *   Whether to get only the top folder.
   *
   * @return mixed
   *   Array of folder objects.
   */
  public function getFolders($gid = NULL, $getTop = NULL) {
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $query = \Drupal::database()->select('ol_folder', 'of');
    $query->addField('of', 'id');
    $query->addField('of', 'name');
    $query->condition('of.group_id', $gid);
    $query->condition('of.status', 1);
    if ($getTop) {
      $query->range(0, 1);
    }
    $query->orderBy('of.name');
    return $query->execute()->fetchAll();
  }

  /**
   * Count files in a folder.
   *
   * @param int $folderId
   *   The folder ID.
   *
   * @return int
   *   Number of files.
   */
  private function countFilesInFolder($folderId) {
    $query = \Drupal::database()->select('ol_file', 'of');
    $query->addField('of', 'folder_id');
    $query->condition('of.folder_id', $folderId);
    $query->condition('of.status', 1);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Get folders dropdown options.
   *
   * @param int|null $groupId
   *   The group ID.
   *
   * @return array
   *   Associative array of folder options.
   */
  public function getFoldersInCurrentGroup($groupId = NULL) {
    $groupId = (empty($groupId)) ? $this->route->getParameter('gid') : $groupId;
    $currentFolders = $this->getFolders($groupId);
    $folders = [];
    $folders[0] = '-- ' . t('Choose folder') . ' --';
    foreach ($currentFolders as $folder) {
      $folders[$folder->id] = $folder->name;
    }
    return $folders;
  }

  /**
   * Check if current user can admin a folder.
   *
   * @param int|null $folderId
   *   The folder ID.
   *
   * @return bool
   *   TRUE if user can admin the folder.
   */
  private function canAdminFolder($folderId = NULL) {
    // If user is group admin, return true.
    if (is_numeric($this->members->isGroupAdmin())) {
      return TRUE;
    }
    // Get folder id, if not provided.
    $folderId = (empty($folderId)) ? $this->route->getParameter('folder_id') : $folderId;
    // User is not group admin of current group, check if user is folder owner.
    $query = \Drupal::database()->select('ol_folder', 'of');
    $query->addField('of', 'user_id');
    $query->condition('of.id', $folderId);
    $query->condition('of.status', 1);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->currentUser->id());
  }

  /**
   * Remove a file from its folder.
   *
   * @param int $id
   *   The file ID.
   * @param int|null $groupId
   *   The group ID.
   */
  public function removeFileFromFolder($id, $groupId = NULL) {
    // Only remove if file owner or group admin.
    if ($this->members->isGroupAdmin() || $this->isFileOwner($id)) {
      $groupId = (empty($groupId)) ? $this->route->getParameter('gid') : $groupId;
      \Drupal::database()->update('ol_file')
        ->fields(['folder_id' => 0])
        ->condition('group_id', $groupId)
        ->condition('id', $id)
        ->execute();
      \Drupal::messenger()->addStatus(t('Your file was successfully removed from folder.'));
    }
    else {
      \Drupal::messenger()->addWarning(t('No access.'));
    }
  }

  /**
   * Check if current user is the file owner.
   *
   * @param int $id
   *   The file ID.
   *
   * @return bool
   *   TRUE if user is the file owner.
   */
  private function isFileOwner($id) {
    $query = \Drupal::database()->select('ol_file', 'fr');
    $query->addField('fr', 'user_id');
    $query->condition('fr.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->currentUser->id());
  }

  /**
   * Get folder name by ID.
   *
   * @param int $folderId
   *   The folder ID.
   *
   * @return string
   *   The folder name.
   */
  private function getFolderName($folderId) {
    $query = \Drupal::database()->select('ol_folder', 'olf');
    $query->addField('olf', 'name');
    $query->condition('olf.id', $folderId);
    return $query->execute()->fetchField();
  }

  /**
   * Place a file in a folder.
   *
   * @param int $id_folder
   *   The folder ID.
   * @param int $id
   *   The file ID.
   * @param int|null $gid
   *   The group ID.
   */
  public function placeFileInFolder($id_folder, $id, $gid = NULL) {
    // Get gid if empty.
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Update file record with folder id.
    \Drupal::database()->update('ol_file')
      ->fields(['folder_id' => $id_folder])
      ->condition('group_id', $gid)
      ->condition('id', $id)
      ->execute();
    // Message.
    \Drupal::messenger()->addStatus(t('Your file was successfully moved.'));
  }

}
