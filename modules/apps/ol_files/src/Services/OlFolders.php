<?php

namespace Drupal\ol_files\Services;

use Drupal\Core\Url;
use Drupal\ol_folder\Entity\OlFolder;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class OlMembers.
 */
class OlFolders{

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
   * OlFolder constructor.
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
  public function saveFolder($name){
    $gid = $this->route->getParameter('gid');
    $folder = OlFolder::create([
      'name' => $name,
      'group_id' => $gid,
    ]);
    $folder->save();
    $id = $folder->id();
    $stream_body = t('Added a folder: @folder', array('@folder' => $name));
    $this->stream->addStreamItem($gid, 'folder_added', $stream_body, 'folder', $id);
    return $id;
  }

  /**
   * @param null $folder_id
   */
  public function removeFolder($folder_id = null){
    // Get current gid.
    $gid = $this->route->getParameter('gid');
    // Check if current user may remove folder.
    if($this->canAdminFolder($folder_id)) {
      // Get folder id, if not provided.
      $folder_id = (empty($folder_id)) ? $this->route->getParameter('folder_id') : $folder_id;
      // Get folder name for stream item.
      $folder_name = $this->getFolderName($folder_id);
      // Delete folder entity.
      \Drupal::database()->delete('ol_folder')
        ->condition('id', $folder_id, '=')
        ->execute();
      // Delete folder reference from files.
      \Drupal::database()->update('ol_file')
        ->fields(['folder_id' => null])
        ->condition('folder_id', $folder_id)
        ->execute();
      // Add stream item.
      $stream_body = t('Removed a folder: @folder', array('@folder' => $folder_name));
      $this->stream->addStreamItem($gid, 'folder_removed', $stream_body, 'folder', $folder_id);
      \Drupal::messenger()->addStatus(t('Folder removed successfully.'));
    }
    $path = Url::fromRoute('ol_files.group_files',['gid' => $gid, 'folder_id' => $folder_id])->toString();
    $response = new RedirectResponse($path);
    $response->send();
  }

  /**
   * @param $name
   * @param null $folder_id
   */
  public function updateFolder($name, $folder_id = null){
    // Check if current user may remove folder.
    if($this->canAdminFolder($folder_id)) {
      \Drupal::database()->update('ol_folder')
        ->fields(['name' => $name])
        ->condition('id', $folder_id)
        ->execute();
    }
  }

  /**
   * @param $gid
   * @return mixed
   */
  function getFoldersData($gid){
    $folders = $this->getFolders($gid);
    foreach ($folders as $folder){
      // Needed for badge.
      $folder->count_files = $this->countFilesInFolder($folder->id);
      // Needed to show/hide drop down.
      $folder->can_admin = $this->canAdminFolder($folder->id);
    }
    return $folders;
  }

  /**
   * @param null $gid
   * @param null $get_top
   *
   * @return mixed
   */
  public function getFolders($gid = null, $get_top = null){
    $gid = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $query = \Drupal::database()->select('ol_folder', 'of');
    $query->addField('of', 'id');
    $query->addField('of', 'name');
    $query->condition('of.group_id', $gid);
    $query->condition('of.status', 1);
    if($get_top) {
      $query->range(0, 1);
    }
    $query->orderBy('of.name');
    return $query->execute()->fetchAll();
  }

  /**
   * @param $folder_id
   * @return mixed
   */
  private function countFilesInFolder($folder_id){
    $query = \Drupal::database()->select('ol_file', 'of');
    $query->addField('of', 'folder_id');
    $query->condition('of.folder_id', $folder_id);
    $query->condition('of.status', 1);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * @param $group_id
   * @return array
   */
  function getFoldersInCurrentGroup($group_id = null){
    $group_id = (empty($group_id)) ? $this->route->getParameter('gid') : $group_id;
    $current_folders = $this->getFolders($group_id);
    $folders = array();
    $folders[0] = '--'. t('Choose folder') .'--';
    foreach ($current_folders as $folder){
      $folders[$folder->id] = $folder->name;
    }
    return $folders;
  }

  /**
   * @param $folder_id
   * @return bool
   */
  private function canAdminFolder($folder_id = null){
    // If user is group admin, return true.
    if(is_numeric($this->members->isGroupAdmin())) {
      return TRUE;
    }
    // Get folder id, if not provided.
    $folder_id = (empty($folder_id)) ? $this->route->getParameter('folder_id') : $folder_id;
    // User is not group admin of current group, check if user is folder owner.
    $query = \Drupal::database()->select('ol_folder', 'of');
    $query->addField('of', 'user_id');
    $query->condition('of.id', $folder_id);
    $query->condition('of.status', 1);
    $uid =  $query->execute()->fetchField();
    return ($uid == $this->current_user->id());
  }

  /**
   * @param $file_id
   * @param null $group_id
   */
  function removeFileFromFolder($id, $group_id = null){
    // Only remove if file owner or group admin
    if($this->members->isGroupAdmin() || $this->isFileOwner($id) ){
      $group_id = (empty($group_id)) ? $this->route->getParameter('gid') : $group_id;
      \Drupal::database()->update('ol_file')
        ->fields(['folder_id' => 0])
        ->condition('group_id', $group_id)
        ->condition('id', $id)
        ->execute();
      \Drupal::messenger()->addStatus(t('Your file was successfully removed from folder.'));
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
  private function isFileOwner($id){
    $query = \Drupal::database()->select('ol_file', 'fr');
    $query->addField('fr', 'user_id');
    $query->condition('fr.id', $id);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->current_user->id());
  }

  /**
   * @param $folder_id
   * @return mixed
   */
  private function getFolderName($folder_id){
    $query = \Drupal::database()->select('ol_folder', 'olf');
    $query->addField('olf', 'name');
    $query->condition('olf.id', $folder_id);
    return $query->execute()->fetchField();
  }

  /**
   * @param $id_folder
   * @param $fid
   * @param null $gid
   */
  public function placeFileInFolder($id_folder, $id, $gid = null){
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
