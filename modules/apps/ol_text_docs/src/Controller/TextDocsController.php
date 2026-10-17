<?php

namespace Drupal\ol_text_docs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_text_docs\Services\OlCategories;
use Drupal\ol_text_docs\Services\OlTextDocs;
use Drupal\ol_weblinks\Services\OlWeblinks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for text document pages.
 */
class TextDocsController extends ControllerBase {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Textdocs service.
   *
   * @var \Drupal\ol_text_docs\Services\OlTextDocs
   */
  protected $textdocs;

  /**
   * Categories service.
   *
   * @var \Drupal\ol_text_docs\Services\OlCategories
   */
  protected $categories;

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
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $route;

  /**
   * Weblinks service.
   *
   * @var \Drupal\ol_weblinks\Services\OlWeblinks
   */
  protected $weblinks;

  /**
   * TextDocsController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   Form builder service.
   * @param \Drupal\ol_text_docs\Services\OlTextDocs $textdocs
   *   Textdocs service.
   * @param \Drupal\ol_text_docs\Services\OlCategories $categories
   *   Categories service.
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
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   Current route match service.
   * @param \Drupal\ol_weblinks\Services\OlWeblinks $weblinks
   *   Weblinks service.
   */
  public function __construct(FormBuilder $formBuilder, OlTextDocs $textdocs, OlCategories $categories, PagerManager $pager, PagerParameters $pager_params, OlComments $comments, OlMembers $members, OlSections $sections, OlGroups $groups, CurrentRouteMatch $route, OlWeblinks $weblinks) {
    $this->formBuilder = $formBuilder;
    $this->textdocs = $textdocs;
    $this->categories = $categories;
    $this->pager = $pager;
    $this->pager_params = $pager_params;
    $this->comments = $comments;
    $this->members = $members;
    $this->sections = $sections;
    $this->groups = $groups;
    $this->route = $route;
    $this->weblinks = $weblinks;
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
      $container->get('current_route_match'),
      $container->get('olweblinks.weblinks')
    );
  }

  /**
   * Get the text docs list page.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return array
   *   Render array for the text docs list page.
   */
  public function getTextDocs($gid) {

    // @todo Convert to parameters instead of direct from query.
    $category_id = \Drupal::request()->query->get('category');
    $current_category = (is_numeric($category_id)) ? $category_id : NULL;
    $group_uuid = $this->groups->getGroupUuidById($gid);

    $total_textdocs_count = $this->getTotalTextDocCount($gid);
    $categories = $this->categories->getCategoriesData($gid);
    $path = \Drupal::request()->getpathInfo();
    $page_title = $this->sections->getSectionOverrideTitle('notebooks', 'Book');

    // Get forms.
    $category_form = $this->formBuilder->getForm('Drupal\ol_text_docs\Form\AddCategoryForm');
    $remove_from_category = $this->formBuilder->getForm('Drupal\ol_text_docs\Form\RemoveTextDocFromCategoryForm');
    $text_doc_form = $this->formBuilder->getForm('Drupal\ol_text_docs\Form\TextDocForm');

    // Pager initialization.
    $page = $this->pager_params->findPage();
    $num_per_page = 9;
    $offset = $num_per_page * $page;

    // Get and render textdocs.
    $textdoc_list_data = $this->textdocs->getTextDocListPage($num_per_page, $offset, FALSE, $current_category);
    $textdocs = $this->textdocs->renderTextDocListPage($textdoc_list_data);
    // Pager, now that we have the total number of results.
    $total_result = $this->textdocs->getTextdocListPage(NULL, NULL, TRUE, $current_category);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();
    // Get remove cat modal.
    $remove_category_html = $this->getRemoveCatModal();
    // Get tree.
    $text_docs_in_group = $this->textdocs->getTextDocsData();
    $text_doc_tree = $this->getTextdocTree($text_docs_in_group, 'text_doc_tree');
    // Get sortable tree for modal.
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
   * Get a single text doc page.
   *
   * @param int $id
   *   The text document ID.
   *
   * @return array
   *   Render array for the text doc page.
   *
   * @throws \Exception
   */
  public function getTextDoc($id) {
    // Get data.
    $categories = $this->categories->getCategoriesData();
    // @todo Check if spoofing is not possible.
    $category_id = \Drupal::request()->query->get('category');
    $current_category = (is_numeric($category_id)) ? $category_id : NULL;
    $data = $this->textdocs->getTextDocData($id);
    $title = $this->textdocs->getTextDocTitle($data);
    $text_doc = $this->textdocs->renderTextDoc($data);
    $comment_form = \Drupal::formBuilder()->getForm('Drupal\ol_main\Form\CommentForm', NULL, NULL, 'text_doc', $id);
    $comment_items = $this->comments->getComments($id, 'text_doc', 'asc');
    // Should move to CommentForm.
    $current_user_picture = $this->members->getUserPictureUrl();
    $gid = \Drupal::service('current_route_match')->getParameter('gid');
    $path = Url::fromRoute('ol_text_docs.textdocs', ['gid' => $gid])->toString();
    $total_textdocs_count = $this->getTotalTextDocCount($gid);
    $remove_category_html = $this->getRemoveCatModal();
    // Get tree.
    $text_docs_in_group = $this->textdocs->getTextDocsData();
    $text_doc_tree = $this->getTextdocTree($text_docs_in_group, 'text_doc_tree', $id);
    // Get sortable tree for modal.
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
   * Get the text document tree HTML.
   *
   * @param array $text_docs_in_group
   *   Array of text documents in the group.
   * @param string $theme
   *   Theme hook to use.
   * @param int|null $currentId
   *   Current text document ID.
   *
   * @return string
   *   Rendered HTML for the tree.
   */
  private function getTextdocTree($text_docs_in_group, $theme, $currentId = NULL) {

    $html = '';
    foreach ($text_docs_in_group as $text_doc) {
      // First, only print uber parents.
      if (!$text_doc->parent_id) {
        // Get children, if any.
        $children_html = $this->getChildrenHtml($text_docs_in_group, $text_doc->id, $theme, $currentId);
        // Build main level menu item.
        $vars['text_doc'] = $text_doc;
        $vars['is_active'] = $text_doc->id == $currentId;
        $vars['children'] = $children_html;
        $render = ['#theme' => $theme, '#vars' => $vars];
        $html .= \Drupal::service('renderer')->render($render);
      }
    }
    return $html;
  }

  /**
   * Get the children HTML for a text document.
   *
   * @param array $text_docs_in_group
   *   Array of text documents in the group.
   * @param int $parentId
   *   Parent text document ID.
   * @param string $theme
   *   Theme hook to use.
   * @param int|null $currentId
   *   Current text document ID.
   *
   * @return string
   *   Rendered HTML for children.
   */
  private function getChildrenHtml($text_docs_in_group, $parentId, $theme, $currentId = NULL) {
    // Get children of this book page.
    $children = $this->getChildren($text_docs_in_group, $parentId);
    // Build children html, also all nested.
    $children_html = '';
    foreach ($children as $child) {
      if (!empty($child)) {
        $vars['text_doc'] = $child;
        $vars['is_active'] = $child->id == $currentId;
        // Recursively check for more children.
        $vars['is_child'] = TRUE;
        $vars['children'] = $this->getChildrenHtml($text_docs_in_group, $child->id, $theme, $currentId);
        $render = ['#theme' => $theme, '#vars' => $vars];
        $children_html .= \Drupal::service('renderer')->render($render);
      }
    }
    return $children_html;
  }

  /**
   * Get children of a text document.
   *
   * @param array $text_docs
   *   Array of text documents.
   * @param int $id
   *   Parent text document ID.
   *
   * @return array
   *   Array of child text documents.
   */
  private function getChildren($text_docs, $id) {
    $children_array = [];
    foreach ($text_docs as $text_doc) {
      if ($text_doc->parent_id == $id) {
        $children_array[] = $text_doc;
      }
    }
    return $children_array;
  }

  /**
   * Get the remove category modal HTML.
   *
   * @return string
   *   Rendered modal HTML.
   */
  private function getRemoveCatModal() {
    // Remove category modal.
    $vars['remove_category_modal'] = \Drupal::formBuilder()->getForm('Drupal\ol_text_docs\Form\DeleteCategoryForm');
    $modal3_render = ['#theme' => 'text_doc_modal_remove_category', '#vars' => $vars];
    return \Drupal::service('renderer')->render($modal3_render);
  }

  /**
   * Get the total text document count for a group.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return int
   *   Total number of text documents.
   */
  private function getTotalTextDocCount($gid) {
    // Count query.
    $query = \Drupal::database()->select('ol_text_doc', 'oltable');
    $query->addField('oltable', 'id');
    $query->condition('oltable.group_id', $gid);
    $query->condition('oltable.status', 1);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Remove a category (needs migration to dynamic form and modal).
   */
  public function removeCategory() {
    $this->categories->removeCategory();
  }

}
