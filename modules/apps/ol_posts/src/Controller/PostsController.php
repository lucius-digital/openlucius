<?php

namespace Drupal\ol_posts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_posts\Form\PostCommentForm;
use Drupal\ol_posts\Form\PostConfigForm;
use Drupal\ol_posts\Form\PostForm;
use Drupal\ol_posts\Services\OlPosts;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for handling posts page requests.
 */
class PostsController extends ControllerBase {

  /**
   * Posts service.
   *
   * @var \Drupal\ol_posts\Services\OlPosts
   */
  protected $posts;

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
   * PostsController constructor.
   *
   * @param \Drupal\ol_posts\Services\OlPosts $posts
   *   Posts service.
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
   */
  public function __construct(OlPosts $posts, PagerManager $pager, PagerParameters $pager_params, OlComments $comments, OlMembers $members, OlSections $sections) {
    $this->posts = $posts;
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
      $container->get('olposts.posts'),
      $container->get('pager.manager'),
      $container->get('pager.parameters'),
      $container->get('olmain.comments'),
      $container->get('olmembers.members'),
      $container->get('olmain.sections')
    );
  }

  /**
   * Render the posts list page.
   *
   * @param int $gid
   *   The group ID.
   *
   * @return array
   *   A render array for the posts list page.
   *
   * @throws \Exception
   */
  public function getPostList($gid) {

    // Pager init.
    $page = $this->pager_params->findPage();
    $num_per_page = 5;
    $offset = $num_per_page * $page;

    // Get posts data.
    $is_group_admin = $this->members->isGroupAdmin();
    $post_settings = $this->posts->getPostSettings();
    $post_form = \Drupal::formBuilder()->getForm(PostForm::class, NULL, NULL, $post_settings);
    $post_config_form = \Drupal::formBuilder()->getForm(PostConfigForm::class, $post_settings);

    $post_list_data = $this->posts->getPostsList(NULL, $num_per_page, $offset, NULL);
    $posts = $this->posts->renderPostsList($post_list_data);
    $page_title = $this->sections->getSectionOverrideTitle('posts', 'Posts');

    // Pager, now that we have the total number of results.
    $total_result = $this->posts->getPostsList(NULL, NULL, NULL, TRUE);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();
    // Used for mentions.
    $group_users = $this->members->getUsersInGroup();

    // Build theme vars.
    $theme_vars = [
      'post_form' => $post_form,
      'post_config_form' => $post_config_form,
      'post_settings' => $post_settings,
      'posts' => $posts,
      'page_title' => $page_title,
      'is_group_admin' => $is_group_admin,
    ];
    // Build render array.
    $render = [];
    $render[] = [
      '#theme' => 'posts_list',
      '#vars' => $theme_vars,
      '#type' => 'remote',
    ];
    // Add pager and return.
    $render[] = [
      '#type' => 'pager',
      '#attached' => [
        'library' => [
          'ol_posts/ol_posts',
        ],
        'drupalSettings' => [
          'users_in_group' => $group_users,
        ],
      ],
    ];
    return $render;
  }

}
