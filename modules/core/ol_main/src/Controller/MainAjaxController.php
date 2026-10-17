<?php

namespace Drupal\ol_main\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\ol_file\Entity\OlFile;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGlobalConfig;
use Drupal\ol_main\Services\OlGroups;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The MainAjaxController .
 */
class MainAjaxController extends ControllerBase {

  /**
   * The OL files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * The global configuration service.
   *
   * @var \Drupal\ol_main\Services\OlGlobalConfig
   */
  protected $config;

  /**
   * The OL groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlGlobalConfig $config, OlFiles $files, OlGroups $groups) {
    $this->config = $config;
    $this->files = $files;
    $this->groups = $groups;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('olmain.global_config'),
          $container->get('olmain.files'),
          $container->get('olmain.groups')
      );
  }

  /**
   * Update the home tabs positions via AJAX.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response indicating success or failure.
   */
  public function updateHomeTabsPositions(Request $request) {
    $ordered_items = $request->get('orderedItems');
    $success = $this->config->updateHomeTabsPositions($ordered_items);
    $response = ($success) ? 1 : 0;
    return new Response($response);
  }

  /**
   * Handle inline image upload via AJAX.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param string $uuid
   *   The group UUID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response with upload result.
   */
  public function uploadInlineImage(Request $request, $uuid) {

    /**
* @var \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile
*/
    $uploadedFile = $request->files->get('file');
    if (!$uploadedFile->isValid()) {
      return $this->makeUploadErrorResponse('Invalid file upload.');
    }
    // Get uploaded file metadata.
    $uploadedFileName = $uploadedFile->getClientOriginalName();
    // $uploadedFileExtension = $uploadedFile->getClientOriginalExtension();
    // $uploadedFileSize = $uploadedFile->getClientSize();
    $uploadedFilePath = $uploadedFile->getPathname();

    // OK. Attach the file.
    // Prepare the directory.
    $gid = $this->groups->getGroupIdByUuid($uuid);
    $hdd_file_location = $this->files->buildFileLocaton('inline_image', $gid);
    $directory = 'private://' . $hdd_file_location;
    $result = \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    if (!$result) {
      return $this->makeUploadErrorResponse('Error preparing directory.');
    }
    // Read the file's contents.
    $fileData = file_get_contents($uploadedFilePath);
    // Save in right dir, creating a file entity instance.
    $savedFile = \Drupal::service('file.repository')->writeData()($fileData, $directory . '/' . $uploadedFileName);
    $this->createOlFile($savedFile, $gid);
    $uri = $savedFile->getFileUri();
    $fid = $savedFile->id();
    $url = Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString($uri));

    if (!$savedFile) {
      return $this->makeUploadErrorResponse('Error saving file.');
    }
    return new JsonResponse(
          [
            'success' => TRUE,
            'url' => $url,
            'fid' => $fid,
          ]
      );
  }

  /**
   * Create an OL file entity from a saved Drupal file.
   *
   * @param \Drupal\file\Entity\File $savedFile
   *   The saved file entity.
   * @param int $gid
   *   The group ID.
   */
  private function createOlFile($savedFile, $gid) {
    $fid = $savedFile->id();
    $name = $savedFile->getFilename();
    // Create ol_file entity.
    $ol_file = OlFile::create(
          [
            'name' => $name,
            'file_id' => $fid,
            'group_id' => $gid,
            'entity_type' => 'inline_image',
          ]
      );
    $ol_file->save();
  }

  /**
   * Make a response for file upload attempt with an error message.
   *
   * @param string $message
   *   The error message.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response to return to client.
   */
  protected function makeUploadErrorResponse($message) {
    $result = [
      'success' => FALSE,
      'message' => $message,
    ];
    return new JsonResponse($result);
  }

}
