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
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class FilesController.
 */
class FilesController extends ControllerBase {


  /**
   * @var $form_builder
   */
  protected $form_builder;

  /**
   * @var $files
   */
  protected $files;

  /**
   * @var $folders
   */
  protected $folders;

  /**
   * @var $pager
   */
  protected $pager;

  /**
   * @var $pager_params
   */
  protected $pager_params;

  /**
   * @var $comments
   */
  protected $comments;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $sections
   */
  protected $sections;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilder $form_builder, OlFiles $files, OlFolders $folders, PagerManager $pager, PagerParameters $pager_params, OlComments $comments, OlMembers $members, OlSections $sections) {
    $this->form_builder = $form_builder;
    $this->files = $files;
    $this->folders = $folders;
    $this->pager = $pager;
    $this->pager_params = $pager_params;
    $this->comments = $comments;
    $this->members = $members;
    $this->sections = $sections;
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
      $container->get('olmain.sections')
    );
  }

  /**
   * @param $gid
   *
   * @return mixed
   */
  public function getFiles($gid){

    // Get data.
    $current_folder = Html::escape(\Drupal::request()->query->get('folder'));
    $total_files_count = getTotalFileCount($gid);
    $folders = $this->folders->getFoldersData($gid);
    $path = \Drupal::request()->getpathInfo();
    $page_title = $this->sections->getSectionOverrideTitle('files', 'Files');

    // Get forms.
    $file_form = $this->form_builder->getForm(\Drupal\ol_files\Form\AddOlFileForm::class);
    $folder_form = $this->form_builder->getForm(\Drupal\ol_files\Form\AddFolderForm::class);
    $remove_from_folder = $this->form_builder->getForm(\Drupal\ol_files\Form\RemoveFileFromFolderForm::class);

    // Pager initialization.
    $page = $this->pager_params->findPage();
    $num_per_page = 20;
    $offset = $num_per_page * $page;

    // Get and render files.
    $file_list_data = $this->files->getFileListPage($num_per_page, $offset, false, $current_folder);
    $files = $this->files->renderFileListPage($file_list_data);

    // Pager, now that we have the total number of results .
    $total_result = $this->files->getFileListPage(null, null, true, $current_folder);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();

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
    ];
    // Build render array.
    $render[] = [
      '#theme' => 'files_list',
      '#vars' => $theme_vars,
      '#type' => 'remote',
      '#attached' => [
        'library' => ['ol_files/ol_files','ol_files/datatables'],
        ],
    ];
    // Add pager to the render array and return.
    $render[] = ['#type' => 'pager'];

    return $render;

  }


  /**
   * Needs to be migrated to dynamic form -and modal.
   */
  public function removeFolder(){
    $this->folders->removeFolder();
  }

}
