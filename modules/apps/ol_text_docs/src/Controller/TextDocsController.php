<?php

namespace Drupal\ol_text_docs\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_text_docs\Services\OlCategories;
use Drupal\ol_text_docs\Services\OlTextDocs;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TextDocsController.
 */
class TextDocsController extends ControllerBase {

  /**
   * @var $form_builder
   */
  protected $form_builder;

  /**
   * @var $textdocs
   */
  protected $textdocs;

  /**
   * @var $categories
   */
  protected $categories;

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
   * @var $groups
   */
  protected $groups;

  /**
   * @var $route
   */
  protected $route;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilder $form_builder, OlTextdocs $textdocs, OlCategories $categories, PagerManager $pager, PagerParameters $pager_params, OlComments $comments, OlMembers $members, OlSections $sections, OlGroups $groups, CurrentRouteMatch $route) {
    $this->form_builder = $form_builder;
    $this->textdocs = $textdocs;
    $this->categories = $categories;
    $this->pager = $pager;
    $this->pager_params = $pager_params;
    $this->comments = $comments;
    $this->members = $members;
    $this->sections = $sections;
    $this->groups = $groups;
    $this->route = $route;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('oltextdocs.textdocs'),
      $container->get('oltextdocs.categories'),
      $container->get('pager.manager'),
      $container->get('pager.parameters'),
      $container->get('olmain.comments'),
      $container->get('olmembers.members'),
      $container->get('olmain.sections'),
      $container->get('olmain.groups'),
      $container->get('current_route_match')
    );
  }

  /**
   * @param $gid
   *
   * @return mixed
   */
  public function getTextDocs($gid){

    $current_category = Html::escape(\Drupal::request()->query->get('category'));
    $group_uuid = $this->groups->getGroupUuidById($gid);

    $total_textdocs_count = $this->getTotalTextDocCount($gid);
    $categories = $this->categories->getCategoriesData($gid);
    $path = \Drupal::request()->getpathInfo();
    $page_title = $this->sections->getSectionOverrideTitle('notebooks', 'Book');

    // Get forms.
    $category_form = $this->form_builder->getForm(\Drupal\ol_text_docs\Form\AddCategoryForm::class);
    $remove_from_category = $this->form_builder->getForm(\Drupal\ol_text_docs\Form\RemoveTextDocFromCategoryForm::class);
    $text_doc_form = $this->form_builder->getForm(\Drupal\ol_text_docs\Form\TextDocForm::class);

    // Pager initialization.
    $page = $this->pager_params->findPage();
    $num_per_page = 9;
    $offset = $num_per_page * $page;

    // Get and render textdocs.
    $textdoc_list_data = $this->textdocs->getTextDocListPage($num_per_page, $offset, false, $current_category);
    $textdocs = $this->textdocs->renderTextDocListPage($textdoc_list_data);
    // Pager, now that we have the total number of results .
    $total_result = $this->textdocs->getTextdocListPage(null, null, true, $current_category);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();
    // Get remove cat modal.
    $remove_category_html = $this->getRemoveCatModal();
    // Get tree.
    $text_docs_in_group = $this->textdocs->getTextDocsData();
    $text_doc_tree = $this->getTextdocTree($text_docs_in_group, 'text_doc_tree');
    // Get sortable tree for modal
    $text_doc_tree_sortable = $this->getTextdocTree($text_docs_in_group, 'text_doc_sortable_tree');
    // Facilitate external users.
    $user = \Drupal::currentUser();
    $can_order = $user->hasPermission('sort book pages');

    // Build theme vars.
    $theme_vars = [
      'textdocs' => $textdocs,
      'category_form' => $category_form,
      'categories' => $categories,
      'path' => $path,
      'current_category' => $current_category,
      'remove_from_category' => $remove_from_category,
      'text_doc_form' => $text_doc_form,
      'page_title' => $page_title,
      'total_textdocs_count' => $total_textdocs_count,
      'remove_category_html' => $remove_category_html,
      'text_doc_tree' => $text_doc_tree,
      'sortable_modal_content' => $text_doc_tree_sortable,
      'can_order' => $can_order,
    ];
    // Build render array.
    $render[] = [
      '#theme' => 'text_docs_list',
      '#vars' => $theme_vars,
      '#type' => 'remote',
      '#attached' => [
        'library' => [
          'ol_text_docs/ol_text_docs',
          'core/sortable',
        ],
        'drupalSettings' => [
          'group_uuid' => $group_uuid,
        ],
      ],
    ];
    // Add pager to the render array and return.
    // No Pager for now: query all, put in datatable.
     $render[] = ['#type' => 'pager'];
    return $render;
  }

  /**
   * @param $id
   *
   * @return array
   * @throws \Exception
   */
  public function getTextDoc($id){
    // Get data.3
    $categories = $this->categories->getCategoriesData();
    $current_category = Html::escape(\Drupal::request()->query->get('category'));
    $data = $this->textdocs->getTextDocData($id);
    $title = $this->textdocs->getTextDocTitle($data);
    $text_doc = $this->textdocs->renderTextDoc($data);
    $comment_form = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\CommentForm::class, null, null, 'text_doc', $id);
    $comment_items = $this->comments->getComments($id, 'text_doc', 'asc');
    $current_user_picture = $this->members->getUserPictureUrl(); // Should move to CommentForm
    $gid = \Drupal::service('current_route_match')->getParameter('gid');
    $path = Url::fromRoute('ol_text_docs.textdocs',['gid' => $gid])->toString();
    $total_textdocs_count = $this->getTotalTextDocCount($gid);
    $remove_category_html = $this->getRemoveCatModal();
    // Get tree.
    $text_docs_in_group = $this->textdocs->getTextDocsData();
    $text_doc_tree = $this->getTextdocTree($text_docs_in_group, 'text_doc_tree', $id);
    // Get sortable tree for modal
    $text_doc_tree_sortable = $this->getTextdocTree($text_docs_in_group, 'text_doc_sortable_tree');
    // Facilitate external users.
    $user = \Drupal::currentUser();
    $can_order = $user->hasPermission('sort book pages');

    // Build it.
    $theme_vars = [
      'text_doc' => $text_doc,
      'title' => $title,
      'path' => $path,
      'categories' => $categories,
      'current_category' => $current_category,
      'comment_form' => $comment_form,
      'comment_items' => $comment_items,
      'current_user_picture' => $current_user_picture,
      'total_textdocs_count' => $total_textdocs_count,
      'remove_category_html' => $remove_category_html,
      'text_doc_tree' => $text_doc_tree,
      'sortable_modal_content' => $text_doc_tree_sortable,
      'can_order' => $can_order,
    ];
    return [
      '#theme' => 'text_doc_page',
      '#vars' => $theme_vars,
      '#attached' => [
        'library' => [
          'ol_text_docs/ol_text_docs',
          'core/sortable',
        ],
      ],
    ];
  }

  /**
   * @param $text_docs_in_group
   * @param $theme
   *
   * @param null $current_id
   *
   * @return string
   */
  private function getTextdocTree($text_docs_in_group, $theme, $current_id = null){

    $html = '';
    foreach ($text_docs_in_group as $text_doc) {
      // First, only print uber parents.
      if (!$text_doc->parent_id) {
        // Get children, if any.
        $children_html = $this->getChildrenHtml($text_docs_in_group, $text_doc->id, $theme, $current_id);
        // Build main level menu item.
        $vars['text_doc'] = $text_doc;
        $vars['is_active'] =  $text_doc->id == $current_id;
        $vars['children'] = $children_html;
        $render = ['#theme' => $theme,'#vars' => $vars];
        $html .= \Drupal::service('renderer')->render($render);
      }
    }
    return $html;
  }

  /**
   * @param $text_docs_in_group
   * @param $parent_id
   * @param $theme
   *
   * @param null $current_id
   *
   * @return string
   */
  private function getChildrenHtml($text_docs_in_group, $parent_id, $theme, $current_id = null){
    // Get children of this book page.
    $children = $this->getChildren($text_docs_in_group, $parent_id);
    // Build children html, also all nested.
    $children_html = '';
    foreach ($children as $child) {
      if (!empty($child)) {
        $vars['text_doc'] = $child;
        $vars['is_active'] = $child->id == $current_id;
        // Recursively check for more children.
        $vars['is_child'] = true;
        $vars['children'] = $this->getChildrenHtml($text_docs_in_group, $child->id, $theme, $current_id);
        $render = ['#theme' => $theme, '#vars' => $vars];
        $children_html .= \Drupal::service('renderer')->render($render);
      }
    }
    return $children_html;
  }

  /**
   * @param $text_docs
   * @param $id
   *
   * @return array
   */
  private function getChildren($text_docs, $id){
    $children_array = [];
    foreach($text_docs as $text_doc){
      if($text_doc->parent_id == $id){
        $children_array[] = $text_doc;
      }
    }
    return $children_array;
  }

  /**
   * @return mixed
   */
  private function getRemoveCatModal(){
    // Remove category modal.
    $vars['remove_category_modal'] = \Drupal::formBuilder()->getForm(\Drupal\ol_text_docs\Form\DeleteCategoryForm::class);
    $modal3_render = ['#theme' => 'text_doc_modal_remove_category','#vars' => $vars];
    return \Drupal::service('renderer')->render($modal3_render);

  }

  /**
   * @param $gid
   *
   * @return mixed
   */
  private function getTotalTextDocCount($gid){
    // Count query.
    $query = \Drupal::database()->select('ol_text_doc', 'oltable');
    $query->addField('oltable', 'id');
    $query->condition('oltable.group_id', $gid);
    $query->condition('oltable.status', 1);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Needs to be migrated to dynamic form -and modal.
   */
  public function removeCategory(){
    $this->categories->removeCategory();
  }

}
