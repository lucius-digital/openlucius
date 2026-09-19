<?php

namespace Drupal\ol_files\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\ol_files\Services\OlFolders;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for file drag-and-drop operations.
 */
class FileDragDropAjaxController extends ControllerBase {

  /**
   * @var $folders
   */
  protected $folders;

  /**
   * {@inheritdoc}
   */
  public function __construct(OlFolders $folders) {
    $this->folders = $folders;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('olfiles.folders'),
    );
  }

  /**
   * Moves a file into a folder.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function moveFile(Request $request): JsonResponse {

    // Get POST values.
    $file_id = $request->request->get('file_id');
    $folder_id = $request->request->get('folder_id');

    // Validate file ID.
    if (!is_numeric($file_id)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Missing file ID.',
      ], 400);
    }

    // Validate folder ID.
    if (!is_numeric($folder_id)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Missing folder ID.',
      ], 400);
    }

    // Update file folder.
    $this->folders->placeFileInFolder($folder_id, $file_id);

    // TODO: get a status returned and provide message to UI.
/*    if ($status) {
      $this->getLogger('ol_files')->error(
        'File @file_id could not be loaded.',
        [
          '@file_id' => $file_id,
        ]
      );

      return new JsonResponse([
        'success' => FALSE,
        'message' => 'File not found.',
      ], 404);
    }*/


    $this->getLogger('ol_files')->notice(
      'File @file_id moved to folder @folder_id.',
      [
        '@file_id' => $file_id,
        '@folder_id' => $folder_id,
      ]
    );

    /*
     * Return success to JavaScript.
     */
    return new JsonResponse([
      'success' => TRUE,
      'file_id' => (int) $file_id,
      'folder_id' => (int) $folder_id,
      'filename' => $file_id,
    ]);
  }

}
