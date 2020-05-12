<?php

namespace Drupal\ol_main\Services;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\ol_file\Entity\OlFile;

/**
 * Class OlGroups.
 */
class OlFiles{

  /**
   * @var $route
   */
  protected $route;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $current_user
   */
  protected $current_user;

  /**
   * @var $folders
   */
  protected $folders;

  /**
   * OlMembers constructor.
   * @param $route
   */
  public function __construct($route, $members, $current_user, $folders) {
    $this->route = $route;
    $this->members = $members;
    $this->current_user = $current_user;
    $this->folders = $folders;
  }

  /**
   * @param $files
   * @param $entity_type
   * @param null $entity_id
   * @param null $folder_id
   *
   * @param null $add_stream
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveFiles($files, $entity_type, $entity_id = null, $folder_id = null, $add_stream = null){
    // Get current gid.
    $group_id = $this->route->getParameter('gid');
    // Needed For stream item message.
    $filenames = array();
    // Loop through uploaded files.
    foreach ($files as $fid) {
      $file = File::load($fid);
      $file->setPermanent();
      $file->save();
      $name = $file->getFilename();
      // Needed For stream item message.
      $filenames [] = $name;
      // Create file_group entity
      $ol_file = OlFile::create([
        'name' => $name,
        'file_id' => $fid,
        'group_id' => $group_id,
        'entity_id' => $entity_id,
        'entity_type' => $entity_type,
        'folder_id' => $folder_id,
      ]);
      $ol_file->save();
      $id = $ol_file->id();
    }
    $files_count = count($filenames);
    $filename =  ($files_count == 1) ?  $filenames[0] : '';
    $filenames_imploded = ($files_count > 1) ? implode(', ', $filenames) : '';

    // We can't have this as dependency, else install profile will bitch during install.
    // So for now, procedural use of this service.
    $stream = \Drupal::service('olstream.stream');
    // Build stream item, based on file count.
    if ($filename && $add_stream){
      $stream_body = t('Added a file: @file', array('@file' => $filename));
      $stream->addStreamItem($group_id, 'file_added', $stream_body, 'file', $id );
    } elseif ($filenames_imploded && $add_stream) {
      $stream_body = t('Added files: @files', array('@files' => $filenames_imploded));
      $stream->addStreamItem($group_id, 'files_added', $stream_body, 'file', $filenames_imploded);
    }
    // Build message, based on file count.
    if ($filename){
      $message = t('One file uploaded successfully: ') .$filename;
    } elseif ($filenames_imploded) {
      $message = $files_count .t(' files uploaded successfully: ') .$filenames_imploded;
    }
    // Add message.
    \Drupal::messenger()->addMessage($message);
  }

  /**
   * @param null $fid
   *
   * @param null $show_in_stream
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeOlFileAndFile($fid = null, $show_in_stream = null){
    // Get parameters from url
    $gid = $this->route->getParameter('gid');
    $fid = ($fid == null) ? $this->route->getParameter('fid') : $fid;
    // Delete if file owner is true.
    if($this->isFileOwner($fid)) {
      // Needed for stream item.
      $name = $this->getFilename($fid);
      // Delete file from hdd.
      $storage = \Drupal::entityTypeManager()->getStorage('file');
      $entities = $storage->loadMultiple([$fid]);
      $storage->delete($entities);
      // Delete reference from dbase (and search index).
      $file_ref_id = $this->getOlFileReferenceIdByFileId($fid);
      $file_ref_entity = OlFile::load($file_ref_id);
      $file_ref_entity->delete();
      // Add stream item.
      if($show_in_stream) {
        // We can't have this as dependency, else install profile will bitch during install.
        // So for now, procedural use of this service.
        $stream = \Drupal::service('olstream.stream');
        $stream_body = t('Removed a file: @file', ['@file' => $name]);
        $stream->addStreamItem($gid, 'file_removed', $stream_body, 'file', $fid);
      }
      // Set message.
      \Drupal::messenger()->addStatus( $name .t(' successfully deleted.'));
    }
  }

  /**
   * This function should be in a service of the ol_files app
   *
   * @param $num_per_page
   * @param $offset
   * @param bool $get_total
   * @param null $folder_id
   *
   * @return mixed
   */
  public function getFileListPage($num_per_page, $offset, $get_total = false, $folder_id = null){
    // Get data
    $group_id = $this->route->getParameter('gid');
    $query = \Drupal::database()->select('ol_file', 'lfr');
    $query->addField('lfr', 'id', 'file_id');
    $query->condition('lfr.group_id', $group_id);
    $query->condition('lfr.status', 1);
    if($folder_id > 0){
      $query->condition('lfr.folder_id', $folder_id);
    }
    $query->orderBy('lfr.file_id', 'desc');
    if ($get_total == false) {
      $query->range($offset, $num_per_page);
    }
    // Data for list.
    if ($get_total == false) {
      $files_data = $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == true) {
      $files_data = $query->countQuery()->execute()->fetchField();
    }
    return $files_data;
  }

  /**
   * @param $file_list_data
   * @return string
   */
  function renderFileListPage($file_list_data){
    // Get data.
    $files_html = '';
    $allowed_extensions = $this->getAllowedFileExtentions();
    $style_ol_filelist = ImageStyle::load('ol_filelist');
    $id_folder =  Html::escape(\Drupal::request()->query->get('folder'));
    $path = \Drupal::request()->getpathInfo();

    // Needed to redirect to current folder, after removing a file from a folder.
    $file_row_data['current_path'] = $path .'?folder='.$id_folder;
    // Needed to show/hide folder options in drop down.
    $file_row_data['has_folders'] = !empty($this->folders->getFolders());
    $file_row_data['owner'] = false;
    // Loop through files and build html.
    foreach ($file_list_data as $file_data) {
      // Get file data details.
      $file = $this->getfileData($file_data->file_id);
      // Build row.
      $file_row_data['group_id'] = $file->group_id;
      $file_row_data['filename'] = shortenString($file->name, 45);
      $file_row_data['uri'] = $file->uri;
      $file_row_data['folder_id'] = $file->folder_id;
      $file_row_data['file_id'] = $file->file_id;
      $file_row_data['created'] = $file->created;
      $file_row_data['user_name'] = $this->members->getUserName($file->user_id);
      $file_row_data['owner'] = $file->user_id == $this->members->getUserId();
      $file_row_data['url'] = Url::fromUri(file_create_url($file->uri));
      $file_row_data['file_size'] = $this->formatBytes($file->filesize,1);
      $file_row_data['foldername'] = $file->foldername;
      $file_row_data['id_folder'] = $file->folder_id;
      //$file_row_data['file_in_folder_form'] = \Drupal::formBuilder()->getForm(\Drupal\ol_files\Form\PlaceFileInFolderForm::class, $file->group_id, $file->file_id);
      // Check if current file is an allowed image, print a thumbnail if so.
      $file_extension = str_replace('image/','', $file->filemime);
      // Check if mime type is available in allowed types.
      if (strpos($allowed_extensions[0], $file_extension) !== false){
        $file_row_data['thumbnail_url'] = $style_ol_filelist->buildUrl($file->uri);
      }
      // Render the html row.
      $render = ['#theme' => 'file_item_list_page', '#vars' => $file_row_data];
      $files_html .= \Drupal::service('renderer')->render($render);
    }
    // Render remove modal, only if user is owner of one of the files.
    $file_remove_modal_html = '';
    $file_in_folder_html = '';
    $remove_folder_html = '';
    if ($file_row_data['owner']){
      // Remove file modal
      $vars['remove_file_modal'] = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\DeleteFileForm::class);
      $modal_render = ['#theme' => 'file_modal_remove','#vars' => $vars];
      $file_remove_modal_html = \Drupal::service('renderer')->render($modal_render);
      // Put file in folder modal.
      $vars['file_in_folder'] = \Drupal::formBuilder()->getForm(\Drupal\ol_files\Form\PlaceFileInFolderForm::class);
      $modal2_render = ['#theme' => 'file_modal_put_in_folder','#vars' => $vars];
      $file_in_folder_html = \Drupal::service('renderer')->render($modal2_render);
      // Remove folder modal.
      $vars['remove_folder_modal'] = \Drupal::formBuilder()->getForm(\Drupal\ol_files\Form\DeleteFolderForm::class);
      $modal3_render = ['#theme' => 'file_modal_remove_folder','#vars' => $vars];
      $remove_folder_html = \Drupal::service('renderer')->render($modal3_render);
    }
    return $files_html .$file_remove_modal_html .$file_in_folder_html .$remove_folder_html;
  }

  /**
   * @param $file_id
   * @return mixed
   */
  private function getfileData($file_id){
    $query = \Drupal::database()->select('ol_file', 'lfr');
    $query->addField('lfr', 'id');
    $query->addField('lfr', 'user_id');
    $query->addField('lfr', 'name');
    $query->addField('lfr', 'created');
    $query->addField('lfr', 'entity_id');
    $query->addField('lfr', 'entity_type');
    $query->addField('lfr', 'file_id');
    $query->addField('lfr', 'folder_id');
    $query->addField('lfr', 'group_id');
    $query->addField('user', 'name', 'username');
    $query->addField('file', 'uri');
    $query->addField('file', 'filemime');
    $query->addField('file', 'filesize');
    $query->addField('folder', 'name', 'foldername');
    $query->condition('lfr.id', $file_id);
    $query->join('users_field_data', 'user', 'user.uid = lfr.user_id');
    $query->join('file_managed', 'file','file.fid = lfr.file_id');
    $query->leftJoin('ol_folder', 'folder','folder.id = lfr.folder_id');
    return $query->execute()->fetchObject();
  }

  /**
   * @param $entity_type
   * @param $entity_id
   *
   * @return string
   */
  public function getAttachedFiles($entity_type, $entity_id){
    // Get data.
    $group_id = $this->route->getParameter('gid');
    $files = $this->getFilesByEntity($group_id, $entity_type, $entity_id);
    $allowed_extensions = $this->getAllowedFileExtentions();
    $image_style = ImageStyle::load('ol_attached_file');
    // Loop through files and build html.
    $files_html = '';
    $file_row_data['owner'] = false;
    foreach ($files as $file) {
      // Build vars.
      $file_row_data['owner'] = $file->uid == $this->members->getUserId();
      $file_row_data['thumbnail_url'] ='';
      $file_row_data['filename'] = $file->filename;
      $file_row_data['uri'] = $file->uri;
      $file_row_data['file_id'] = $file->file_id;
      $file_row_data['url'] = Url::fromUri(file_create_url($file->uri));
      $file_row_data['file_size'] = $this->formatBytes($file->filesize,1);
      // Check if current file is an allowed image, print a thumbnail if so.
      $file_extension = str_replace('image/','', $file->filemime);
      // Check if mime type is available in allowed types.
      if (strpos($allowed_extensions[0], $file_extension) !== false){
        $file_row_data['thumbnail_url'] = $image_style->buildUrl($file->uri);
      }
      // Render HTML.
      $render = ['#theme' => 'file_item',
                  '#vars' => $file_row_data,
                  '#attached' => ['library' => 'ol_main/ol_attached_files',],
                ];  // Library renders multiple times, but only 1 css visible, that's good.
                    // But not too much unneeded load here..?
      $files_html .= \Drupal::service('renderer')->render($render);
    }
    // Render remove modal, only if user is owner of one of the files.
    $file_remove_modal_html = '';
    if ($file_row_data['owner']){
      $vars['remove_file_modal'] = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\DeleteFileForm::class);
      $modal_render = ['#theme' => 'file_modal_remove','#vars' => $vars];
      $file_remove_modal_html = \Drupal::service('renderer')->render($modal_render);
    }
    // Return files and remove modal html.
    return $files_html .$file_remove_modal_html;
  }

  /**
   * @param $group_id
   * @param $entity_type
   * @param $entity_id
   *
   * @return mixed
   */
  private function getFilesByEntity($group_id, $entity_type, $entity_id){
    // Get file detail data.
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('olf', 'file_id');
    $query->addField('file', 'filename');
    $query->addField('file', 'uid');
    $query->addField('file', 'uri');
    $query->addField('file', 'filemime');
    $query->addField('file', 'filesize');
    $query->addField('olf', 'entity_type');
    $query->addField('olf', 'entity_id');
    $query->condition('olf.group_id', $group_id);
    $query->condition('olf.entity_id', $entity_id);
    $query->condition('olf.entity_type', $entity_type);
    $query->join('file_managed', 'file','file.fid = olf.file_id');
    return $query->execute()->fetchAll();
  }

  /**
   * @param $fid
   * @return mixed
   */
  private function getOlFileReferenceIdByFileId($fid){
    $query = \Drupal::database()->select('ol_file', 'fr');
    $query->addField('fr', 'id');
    $query->condition('fr.file_id', $fid);
    return $query->execute()->fetchField();
  }

  /**
   * @param $fid
   * @return bool
   */
  private function isFileOwner($fid){
    $query = \Drupal::database()->select('ol_file', 'fr');
    $query->addField('fr', 'user_id');
    $query->condition('fr.file_id', $fid);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->current_user->id());
  }

  /**
   * @return array
   */
  public function getAllowedFileExtentions(){
    return array('jpg jpeg gif png txt doc docx zip xls xlsx pdf ppt pps odt ods odp');
  }

  /**
   * @param $entity_type
   *
   * @return string
   */
  public function buildFileLocaton($entity_type){
    $group_id = $this->route->getParameter('gid');
    $uid = $this->current_user->id();
    return $group_id .'/'.$entity_type.'/'.date('Y_W'.'/'.$uid);
    }

  /**
   * @param $fid
   * @return mixed
   */
  private function getFileName($fid){
    $query = \Drupal::database()->select('file_managed', 'fmn');
    $query->addField('fmn', 'filename');
    $query->condition('fmn.fid', $fid);
    return $query->execute()->fetchField();
  }
  /**
   * @param $size
   * @param int $precision
   * @return string
   * Source: https://stackoverflow.com/questions/2510434/format-bytes-to-kilobytes-megabytes-gigabytes
   */
  private function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
  }

}
