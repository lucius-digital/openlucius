<?php

namespace Drupal\ol_main\Services;

use DOMDocument;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\ol_file\Entity\OlFile;
use Drupal\ol_files\Form\PlaceFileInFolderForm;
use Drupal\ol_main\Form\DeleteFileForm;

/**
 * Service for managing file operations.
 */
class OlFiles {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route;

  /**
   * The OL members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs an OlFiles instance.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   The current route match.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   The OL members service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   */
  public function __construct($route, $members, $current_user) {
    $this->route = $route;
    $this->members = $members;
    $this->currentUser = $current_user;
  }

  /**
   * Save uploaded files and create OL file entities.
   *
   * @param array $files
   *   Array of file IDs.
   * @param string $entity_type
   *   The entity type the files are attached to.
   * @param int|null $entity_id
   *   The entity ID.
   * @param int|null $folder_id
   *   The folder ID.
   * @param bool|null $add_stream
   *   Whether to add a stream item.
   * @param int|null $gid
   *   The group ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveFiles($files, $entity_type, $entity_id = NULL, $folder_id = NULL, $add_stream = NULL, $gid = NULL) {
    // Get current gid.
    $group_id = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    // Needed For stream item message.
    $filenames = [];
    $file_ids = [];
    // Loop through uploaded files.
    foreach ($files as $fid) {
      $file = File::load($fid);
      $file->setPermanent();
      $file->save();
      // Needed For stream item.
      $new_fid = $file->id();
      $file_ids[] = $new_fid;
      $name = $file->getFilename();
      $filenames[] = $name;
      // Create ol_file entity.
      $ol_file = OlFile::create(
            [
              'name' => $name,
              'file_id' => $fid,
              'group_id' => $group_id,
              'entity_id' => $entity_id,
              'entity_type' => $entity_type,
              'folder_id' => $folder_id,
            ]
        );
      $ol_file->save();
      $id = $ol_file->id();
    }
    $files_count = count($filenames);
    $filename = ($files_count == 1) ? $filenames[0] : '';
    $filenames_imploded = ($files_count > 1) ? implode(', ', $filenames) : '';
    $file_ids_json = ($files_count > 1) ? json_encode($file_ids) : '';

    // Procedural use to avoid install profile dependency issues.
    $stream = \Drupal::service('olstream.stream');
    // Build stream item, based on file count.
    if ($filename && $add_stream) {
      $stream->addStreamItem($group_id, 'file_added', $filename, 'files', $new_fid);
    }
    elseif ($filenames_imploded && $add_stream) {
      $stream_body = t('Added files: @files', ['@files' => $filenames_imploded]);
      $stream->addStreamItem($group_id, 'files_added', $stream_body, 'files', $file_ids_json);
    }
    // Build message, based on file count.
    if ($filename) {
      $message = t('One file uploaded successfully: @filename', ['@filename' => $filename]);
    }
    elseif ($filenames_imploded) {
      $message = t('@count files uploaded successfully: @filenames', [
        '@count' => $files_count,
        '@filenames' => $filenames_imploded,
      ]);
    }
    // Add message.
    \Drupal::messenger()->addMessage($message);

    return $id;
  }

  /**
   * Remove an OL file entity and its associated Drupal file.
   *
   * @param int|null $fid
   *   The file ID.
   * @param bool $show_in_stream
   *   Whether to show in the activity stream.
   * @param bool $force_delete
   *   Whether to force deletion regardless of ownership.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeOlFileAndFile($fid = NULL, $show_in_stream = FALSE, $force_delete = FALSE) {
    // Get parameters from url.
    $gid = $this->route->getParameter('gid');
    $fid = ($fid == NULL) ? $this->route->getParameter('fid') : $fid;
    // Delete if file owner is true.
    if ($this->isFileOwner($fid) || $force_delete === TRUE) {
      // Needed for stream item.
      $name = $this->getFilename($fid);

      // But unpublish:
      $entity = File::load($fid);
      if ($entity) {
        $entity->set("status", 0);
        $entity->save();
      }

      // Delete reference from dbase (and search index).
      $ol_file_id = $this->getOlFileReferenceIdByFileId($fid);
      $ol_entity = OlFile::load($ol_file_id);
      $ol_entity->set("status", 0);
      $ol_entity->save();

      // And build log
      // Get current url.
      $current_uri = \Drupal::request()->getRequestUri();
      \Drupal::logger('inline_file_removed')->warning(
            '<pre><code>
            uri: ' . $current_uri . '
            fid: ' . $fid . '
            ol_fid: ' . $ol_file_id . '
        </code></pre>'
        );

      // Delete file from HDD.
      // @todo Let cron do this for files deleted xx days ago for easy restore.
      /* $storage = \Drupal::entityTypeManager()->getStorage('file');
      $entities = $storage->loadMultiple([$fid]);
      $storage->delete($entities);
      // Delete reference from dbase (and search index).
      $file_ref_id = $this->getOlFileReferenceIdByFileId($fid);
      $file_ref_entity = OlFile::load($file_ref_id);
      $file_ref_entity->delete();*/

      // Add stream item.
      if ($show_in_stream == TRUE) {
        // Procedural use to avoid install profile dependency issues.
        $stream = \Drupal::service('olstream.stream');
        $stream->addStreamItem($gid, 'file_removed', $name, 'files', $fid);
      }
      // Set message.
      \Drupal::messenger()->addStatus(t('@name successfully deleted.', ['@name' => $name]));
    }
  }

  /**
   * Get a paginated list of files for the current group.
   *
   * @param int $num_per_page
   *   Number of files per page.
   * @param int $offset
   *   Offset for pagination.
   * @param bool $get_total
   *   Whether to return the total count instead of file data.
   * @param int|null $folder_id
   *   Optional folder ID filter.
   *
   * @return mixed
   *   File data array or total count.
   */
  public function getFileListPage($num_per_page, $offset, $get_total = FALSE, $folder_id = NULL) {
    // Get data.
    $group_id = $this->route->getParameter('gid');
    $query = \Drupal::database()->select('ol_file', 'lfr');
    $query->addField('lfr', 'id');
    $query->addField('lfr', 'entity_type');
    if ($get_total == FALSE) {
      // Needed for text docs.
      $query->addField('lfr', 'file_id');
    }
    $query->condition('lfr.group_id', $group_id);
    $query->condition('lfr.status', 1);
    if ($folder_id > 0) {
      $query->condition('lfr.folder_id', $folder_id);
    }
    $query->orderBy('lfr.created', 'desc');
    if ($get_total == FALSE) {
      $query->range($offset, $num_per_page);
    }
    // Data for list.
    if ($get_total == FALSE) {
      $files_data = $query->execute()->fetchAll();
    }
    // Count data for pager.
    elseif ($get_total == TRUE) {
      $files_data = $query->countQuery()->execute()->fetchField();
    }
    return $files_data;
  }

  /**
   * Render HTML for a list of files.
   *
   * @param array $file_list_data
   *   Array of file data objects.
   *
   * @return string
   *   Rendered HTML for the file list.
   */
  public function renderFileListPage($file_list_data) {
    // Get data.
    $files_html = '';
    $owner_show_modals = FALSE;
    // $id_folder =  Html::escape(\Drupal::request()->query->get('folder'));
    // $path = \Drupal::request()->getpathInfo();
    // Procedural use to avoid install profile dependency issues.
    $folders = \Drupal::service('olfiles.folders');
    $has_folders = !empty($folders->getFolders());
    // Needed to redirect to current folder, after removing
    // a file from a folder.
    // $file_row_data['current_path'] = $path .'?folder='.$id_folder;
    // Needed to show/hide folder options in drop down.
    // Loop through files and build html.
    foreach ($file_list_data as $file_data) {
      // Get file details.
      $extension_icons = $this->getExtensionIcons();
      $file_row_data = $this->buildFileDetails($file_data->id, $extension_icons);
      // If current user is owner in 1 of the records: show modals.
      if ($file_row_data['owner'] == 1) {
        $owner_show_modals = TRUE;
      }
      // Needed to show/hide folder drop down items.
      $file_row_data['has_folders'] = $has_folders;
      // Render the html row.
      $render = ['#theme' => 'file_item_list_page', '#vars' => $file_row_data];
      $files_html .= \Drupal::service('renderer')->render($render);
    }
    // Render modals, only if user is owner of one of the files.
    $file_remove_modal_html = '';
    $file_in_folder_html = '';
    $remove_folder_html = '';

    // If current user is owner in 1 of the records: show modals.
    if ($owner_show_modals) {
      // Remove file modal.
      $vars['remove_file_modal'] = \Drupal::formBuilder()->getForm(DeleteFileForm::class);
      $modal_render = ['#theme' => 'file_modal_remove', '#vars' => $vars];
      $file_remove_modal_html = \Drupal::service('renderer')->render($modal_render);
      // Put file in folder modal.
      $vars['file_in_folder'] = \Drupal::formBuilder()->getForm(PlaceFileInFolderForm::class);
      $modal2_render = ['#theme' => 'file_modal_put_in_folder', '#vars' => $vars];
      $file_in_folder_html = \Drupal::service('renderer')->render($modal2_render);
    }
    return $files_html . $file_remove_modal_html . $file_in_folder_html;
  }

  /**
   * Build an array of file details for theming.
   *
   * @param int $id
   *   The OL file ID.
   * @param array|null $extension_icons
   *   Array of extension icons.
   *
   * @return array
   *   File details array.
   */
  private function buildFileDetails($id, $extension_icons = NULL) {
    // Get file data details.
    $file = $this->getfileData($id);
    // Build row.
    $file_row_data['id'] = $id;
    $file_row_data['group_id'] = $file->group_id;
    $file_row_data['filename'] = $file->name;
    $file_row_data['uri'] = $file->uri;
    $file_row_data['folder_id'] = $file->folder_id;
    $file_row_data['ol_fid'] = $file->ol_fid;
    $file_row_data['created'] = $file->created;
    // Only show this, if it's not a stand alone uploaded file.
    $file_row_data['entity_type'] = ($file->entity_type != 'file') ? str_replace("_", " ", $file->entity_type) : NULL;
    $file_row_data['user_name'] = shortenString($this->members->getUserName($file->user_id), 25);
    $file_row_data['owner'] = $file->user_id == $this->members->getUserId();
    $file_row_data['url'] = Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString(($file->uri)));
    $file_row_data['file_size'] = $this->formatBytes($file->filesize, 1);
    $file_row_data['foldername'] = $file->foldername;
    $file_row_data['id_folder'] = $file->folder_id;
    $file_row_data['file_type'] = 'file';
    // Check if current file is an allowed image, print a thumbnail if so.
    $file_extension = str_replace('image/', '', $file->filemime);
    // Check if mime type is available in allowed types.
    $allowed_extensions = $this->getAllowedFileExtentions();
    $style_ol_filelist = ImageStyle::load('ol_filelist');
    if (strpos($allowed_extensions[0], $file_extension) !== FALSE) {
      $file_row_data['thumbnail_url'] = $style_ol_filelist->buildUrl($file->uri);
    }
    else {
      // Get icon based on extension.
      $extension = substr(strrchr($file->name, '.'), 1);
      if (!empty($extension_icons[$extension])) {
        $file_row_data['extension_icon'] = $extension_icons[$extension];
      }
      else {
        $file_row_data['extension_icon'] = (empty($file_row_data['extension_icon'])) ? 'bi bi-file-earmark' : $file_row_data['extension_icon'];
      }
    }
    return $file_row_data;
  }

  /**
   * Get a mapping of file extensions to icon classes.
   *
   * @return array
   *   Associative array of extensions to icon classes.
   */
  private function getExtensionIcons() {
    return [
      'xlsx' => 'bi bi-file-earmark-spreadsheet',
      'xls' => 'bi bi-file-earmark-spreadsheet',
      'ods' => 'bi bi-file-earmark-spreadsheet',
      'doc' => 'bi bi-file-word',
      'docx' => 'bi bi-file-word',
      'ppt' => 'bi bi-file-earmark-ppt',
      'pps' => 'bi bi-file-earmark-slides',
      'odp' => 'bi bi-file-earmark-slides',
      'pptx' => 'bi bi-file-earmark-ppt',
      'zip' => 'bi bi-file-earmark-zip',
      'pdf' => 'bi bi-file-earmark-richtext',
      'odt' => 'bi bi-file-earmark-richtext',
      'txt' => 'bi bi-file-earmark-text',
    ];
  }

  /**
   * Get file data from the database by OL file ID.
   *
   * @param int $file_id
   *   The OL file ID.
   *
   * @return object
   *   File data object.
   */
  private function getfileData($file_id) {
    $query = \Drupal::database()->select('ol_file', 'lfr');
    $query->addField('lfr', 'id', 'ol_fid');
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
    $query->join('file_managed', 'file', 'file.fid = lfr.file_id');
    $query->leftJoin('ol_folder', 'folder', 'folder.id = lfr.folder_id');
    return $query->execute()->fetchObject();
  }

  /**
   * Get rendered HTML for files attached to an entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity ID.
   * @param string $image_preset
   *   The image style preset.
   * @param array|null $fids
   *   Optional array of file IDs to filter.
   * @param int|null $gid
   *   Optional group ID.
   *
   * @return string
   *   Rendered HTML for attached files.
   */
  public function getAttachedFiles($entity_type, $entity_id, $image_preset = 'ol_attached_file', $fids = NULL, $gid = NULL) {
    // Get data.
    $group_id = (empty($gid)) ? $this->route->getParameter('gid') : $gid;
    $files = $this->getFilesByEntity($group_id, $entity_type, $entity_id, $fids);
    $allowed_extensions = $this->getAllowedFileExtentions();
    $image_style = ImageStyle::load($image_preset);
    // Loop through files and build html.
    $files_html = '';
    $file_row_data['owner'] = FALSE;

    // Loop though files, build vars and html.
    foreach ($files as $file) {
      // Work around for ajax file delete, only in tasks atm.
      // If task comment file: flag true.
      if (!empty($file->comment_entity_type)) {
        $file_row_data['is_task_file'] = ($file->comment_entity_type == 'task');
      }
      // No task comment file, maybe task file?
      if ($entity_type == 'task') {
        $file_row_data['is_task_file'] = TRUE;
      }
      $file_row_data['thumbnail_url'] = '';
      $file_row_data['owner'] = $file->uid == $this->members->getUserId();
      $file_row_data['olf'] = $file->created;
      $file_row_data['username'] = shortenString($file->username, 15);
      $file_row_data['big_image'] = $image_preset == 'large';
      $file_row_data['post_image'] = $image_preset == 'post_image';
      $file_row_data['filename'] = shortenString($file->filename, 50);
      $file_row_data['uri'] = $file->uri;
      $file_row_data['ol_fid'] = $file->ol_fid;
      $file_row_data['url'] = Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString($file->uri));
      $file_row_data['file_size'] = $this->formatBytes($file->filesize, 1);
      // Check if current file is an allowed image, print a thumbnail if so.
      $file_extension = str_replace('image/', '', $file->filemime);
      // Check if mime type is available in allowed types.
      if (strpos($allowed_extensions[0], $file_extension) !== FALSE) {
        $file_row_data['thumbnail_url'] = $image_style->buildUrl($file->uri);
      }
      // Needed, to fill correct file_type in 'remove file modal'.
      $file_row_data['file_type'] = 'file';
      // Render HTML.
      $render = [
        '#theme' => 'file_item',
        '#vars' => $file_row_data,
        '#attached' => ['library' => 'ol_main/ol_attached_files'],
      // Library renders multiple times, but only 1 css visible, that's good.
      ];
      // But not too much unneeded load here..?
      $files_html .= \Drupal::service('renderer')->render($render);
    }
    // Render remove modal, only if user is owner of one of the files.
    $file_remove_modal_html = '';
    if ($file_row_data['owner']) {
      $vars['remove_file_modal'] = \Drupal::formBuilder()->getForm(DeleteFileForm::class);
      $modal_render = ['#theme' => 'file_modal_remove', '#vars' => $vars];
      $file_remove_modal_html = \Drupal::service('renderer')->render($modal_render);
    }
    // Return files and remove modal html.
    return $files_html . $file_remove_modal_html;
  }

  /**
   * Get files attached to a specific entity.
   *
   * @param int $group_id
   *   The group ID.
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity ID.
   * @param array|null $fids
   *   Optional array of file IDs to filter.
   *
   * @return array
   *   Array of file data objects.
   */
  public function getFilesByEntity($group_id, $entity_type, $entity_id, $fids = NULL) {

    // This is to facilitate ajax calls that use uuid, like comments.
    // We should clean this up, make all consistent.
    if (empty($group_id)) {
      $group_uuid = $this->route->getParameter('uuid');
      $groups = \Drupal::service('olmain.groups');
      $group_id = $groups->getGroupIdByUuid($group_uuid);
    }

    // Preparing to figure out why we can only append new images.
    $fids = ($fids) ? implode(',', $fids) : NULL;
    // Get file detail data.
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('olf', 'id', 'ol_fid');
    $query->addField('olf', 'created');
    $query->addField('file', 'filename');
    $query->addField('file', 'uid');
    $query->addField('file', 'uri');
    $query->addField('file', 'filemime');
    $query->addField('file', 'filesize');
    $query->addField('olf', 'entity_type');
    $query->addField('olf', 'entity_id');
    $query->addField('ufd', 'name', 'username');
    $query->condition('olf.group_id', $group_id);
    $query->condition('olf.entity_id', $entity_id);
    $query->condition('olf.entity_type', $entity_type);
    $query->condition('olf.status', 1);
    $query->join('file_managed', 'file', 'file.fid = olf.file_id');
    $query->join('users_field_data', 'ufd', 'ufd.uid = olf.user_id');
    if ($entity_type == 'comment') {
      $query->addField('comment', 'entity_type', 'comment_entity_type');
      $query->join('ol_comment', 'comment', 'comment.id = olf.entity_id');
    }
    return $query->execute()->fetchAll();
  }

  /**
   * Get the OL file reference ID by Drupal file ID.
   *
   * @param int $fid
   *   The Drupal file ID.
   *
   * @return int
   *   The OL file reference ID.
   */
  private function getOlFileReferenceIdByFileId($fid) {
    $query = \Drupal::database()->select('ol_file', 'fr');
    $query->addField('fr', 'id');
    $query->condition('fr.file_id', $fid);
    return $query->execute()->fetchField();
  }

  /**
   * Check if the current user is the owner of a file.
   *
   * @param int $fid
   *   The Drupal file ID.
   *
   * @return bool
   *   TRUE if the current user is the owner.
   */
  private function isFileOwner($fid) {
    $query = \Drupal::database()->select('ol_file', 'fr');
    $query->addField('fr', 'user_id');
    $query->condition('fr.file_id', $fid);
    $uid = $query->execute()->fetchField();
    return ($uid == $this->currentUser->id());
  }

  /**
   * Get allowed file extensions for uploads.
   *
   * @return array
   *   Array of allowed file extensions.
   */
  public function getAllowedFileExtentions() {
    return ['jpg jpeg gif png txt doc docx zip xls xlsx pdf ppt pps odt ods odp wav mp3 md'];
  }

  /**
   * Get allowed image extensions for uploads.
   *
   * @return array
   *   Array of allowed image extensions.
   */
  public function getAllowedImageExtentions() {
    return ['extensions' => 'jpg jpeg gif png'];
  }

  /**
   * Build the file storage location path.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int|null $group_id
   *   The group ID.
   *
   * @return string
   *   The file location path.
   */
  public function buildFileLocaton($entity_type, $group_id = NULL) {
    $group_id = (is_null($group_id)) ? $this->route->getParameter('gid') : $group_id;
    $uid = $this->currentUser->id();
    return $group_id . '/' . $entity_type . '/' . date('Y_W') . '/' . $uid;
  }

  /**
   * Get the file URI by file ID.
   *
   * @param int $fid
   *   The file ID.
   *
   * @return int
   *   The file URI.
   */
  public function getFileUri($fid) {
    $query = \Drupal::database()->select('file_managed', 'file');
    $query->addField('file', 'uri');
    $query->condition('file.fid', $fid);
    return $query->execute()->fetchField();
  }

  /**
   * Get the file name by file ID.
   *
   * @param int $fid
   *   The file ID.
   *
   * @return mixed
   *   The file name.
   */
  public function getFileName($fid) {
    $query = \Drupal::database()->select('file_managed', 'fmn');
    $query->addField('fmn', 'filename');
    $query->condition('fmn.fid', $fid);
    return $query->execute()->fetchField();
  }

  /**
   * Get the Drupal file ID by OL file ID.
   *
   * @param int $ol_file_id
   *   The OL file ID.
   *
   * @return mixed
   *   The Drupal file ID.
   */
  public function getFileId($ol_file_id) {
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('olf', 'file_id');
    $query->condition('olf.id', $ol_file_id);
    return $query->execute()->fetchField();
  }

  /**
   * Format bytes into a human-readable string.
   *
   * @param int $size
   *   Size in bytes.
   * @param int $precision
   *   Decimal precision.
   *
   * @return string
   *   Formatted size string.
   *
   *   Source: https://stackoverflow.com/...
   */
  private function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = ['', 'KB', 'MB', 'GB', 'TB'];
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
  }

  /**
   * Delete inline files that were removed from content.
   *
   * @param string $old_content
   *   The old content HTML.
   * @param string $new_content
   *   The new content HTML.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteInlineFile($old_content, $new_content) {
    // Get fids from inline images, old and new.
    $fids_old = $this->getFidsFromContent($old_content);
    $fids_new = $this->getFidsFromContent($new_content);
    // Leave only fids that are not present anymore in new content.
    if (is_array($fids_old) && !empty($fids_old)) {
      $fids_delete = array_diff($fids_old, $fids_new);
      // Delete $fids (files) that are not there anymore.
      foreach ($fids_delete as $fid_delete) {
        // Remove file.
        $this->removeOlFileAndFile($fid_delete, FALSE, TRUE);
      }
    }
  }

  /**
   * Extract file IDs from HTML content.
   *
   * @param string $content
   *   HTML content containing image tags.
   *
   * @return array
   *   Array of file IDs found in content.
   */
  private function getFidsFromContent($content) {
    // @todo , not working (for inline files), this generates warnings.
    /*
    if (empty($content)) {
      return [];
    }
    $dom = new domDocument();
    $dom->loadHTML(html_entity_decode($content));
    $dom->preserveWhiteSpace = FALSE;
    $imgs = $dom->getElementsByTagName("img");
    $fids = [];
    for ($i = 0; $i < $imgs->length; $i++) {
      $fids[] = $imgs->item($i)->getAttribute("data-fid");
    }
    return $fids;
    */
  }

}
