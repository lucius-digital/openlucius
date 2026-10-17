<?php

namespace Drupal\ol_files\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\Core\Url;
use Drupal\ol_files\Services\OlFolders;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for handling files page requests.
 */
class FilesController extends ControllerBase {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * Folders service.
   *
   * @var \Drupal\ol_files\Services\OlFolders
   */
  protected $folders;

  /**
   * Pager manager service.
   *
   * @var \Drupal\Core\Pager\PagerManager
   */
  protected $pager;

  /**
   * Pager parameters service.
   *
   * @var \Drupal\Core\Pager\PagerParameters
   */
  protected $pager_params;

  /**
   * Comments service.
   *
   * @var \Drupal\ol_main\Services\OlComments
   */
  protected $comments;

  /**
   * Members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * Sections service.
   *
   * @var \Drupal\ol_main\Services\OlSections
   */
  protected $sections;

  /**
   * Groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * FilesController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   Form builder service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   Files service.
   * @param \Drupal\ol_files\Services\OlFolders $folders
   *   Folders service.
   * @param \Drupal\Core\Pager\PagerManager $pager
   *   Pager manager service.
   * @param \Drupal\Core\Pager\PagerParameters $pager_params
   *   Pager parameters service.
   * @param \Drupal\ol_main\Services\OlComments $comments
   *   Comments service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
   * @param \Drupal\ol_main\Services\OlSections $sections
   *   Sections service.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   Groups service.
   */
  public function __construct(FormBuilder $formBuilder, OlFiles $files, OlFolders $folders, PagerManager $pager, PagerParameters $pager_params, OlComments $comments, OlMembers $members, OlSections $sections, OlGroups $groups) {
    $this->formBuilder = $formBuilder;
    $this->files = $files;
    $this->folders = $folders;
    $this->pager = $pager;
    $this->pager_params = $pager_params;
    $this->comments = $comments;
    $this->members = $members;
    $this->sections = $sections;
    $this->groups = $groups;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('olmain.files'),
      $container->get('olfiles.folders'),
      $container->get('pager.manager'),
      $container->get('pager.parameters'),
      $container->get('olmain.comments'),
      $container->get('olmembers.members'),
      $container->get('olmain.sections'),
      $container->get('olmain.groups')
    );
  }

  /**
   * Render the files list page.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return array
   *   A render array for the files page.
   */
  public function getFiles($gid) {

    // Get data.
    // TODO, convert query get to parameter get.
    $folder_id = \Drupal::request()->query->get('folder');
    $current_folder = (is_numeric($folder_id)) ? $folder_id : NULL;
    $total_files_count = getTotalFileCount($gid);
    $folders = $this->folders->getFoldersData($gid);
    $path = \Drupal::request()->getpathInfo();
    $page_title = $this->sections->getSectionOverrideTitle('files', 'Files');

    // Get forms.
    $file_form = $this->formBuilder->getForm(\Drupal\ol_files\Form\AddOlFileForm::class);
    $folder_form = $this->formBuilder->getForm(\Drupal\ol_files\Form\AddFolderForm::class);
    $remove_from_folder = $this->formBuilder->getForm(\Drupal\ol_files\Form\RemoveFileFromFolderForm::class);

    // Pager initialization.
    $page = $this->pager_params->findPage();
    $num_per_page = 20;
    $offset = $num_per_page * $page;

    // Get and render files.
    $file_list_data = $this->files->getFileListPage($num_per_page, $offset, FALSE, $current_folder);
    $files = $this->files->renderFileListPage($file_list_data);

    // Pager, now that we have the total number of results.
    $total_result = $this->files->getFileListPage(NULL, NULL, TRUE, $current_folder);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();

    $group_id = $this->groups->getCurrentGroupId();

    // Remove folder modal.
    $vars['remove_folder_modal'] = \Drupal::formBuilder()->getForm(\Drupal\ol_files\Form\DeleteFolderForm::class);
    $remove_folder_modal = ['#theme' => 'file_modal_remove_folder', '#vars' => $vars];

    // Build theme vars.
    $theme_vars = [
      'file_form' => $file_form,
      'files' => $files,
      'folder_form' => $folder_form,
      'folders' => $folders,
      'path' => $path,
      'current_folder' => $current_folder,
      'remove_from_folder' => $remove_from_folder,
      'page_title' => $page_title,
      'total_files_count' => $total_files_count,
      'remove_folder_modal' => $remove_folder_modal,
    ];
    // Build render array.
    $render[] = [
      '#theme' => 'files_list',
      '#vars' => $theme_vars,
      '#type' => 'remote',
      '#attached' => [
        'library' => ['ol_files/ol_files','ol_files/datatables'],
        'drupalSettings' => [
          'group_id' => $group_id,
        ],
      ],
    ];
    // Add pager to the render array and return.
    $render[] = ['#type' => 'pager'];

    return $render;

  }


  /**
   * @todo Needs to be migrated to dynamic form and modal.
   */
  public function removeFolder() {
    $this->folders->removeFolder();
  }

}
