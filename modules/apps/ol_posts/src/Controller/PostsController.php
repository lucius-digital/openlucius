<?php

namespace Drupal\ol_posts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParameters;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_posts\Services\OlPosts;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class postController.
 */
class PostsController extends ControllerBase {

  /**
   * @var $comments
   */
  protected $posts;

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
   * Constructor
   *
   * @param \Drupal\ol_posts\Services\OlPosts $posts
   * @param \Drupal\Core\Pager\PagerManager $pager
   * @param \Drupal\Core\Pager\PagerParameters $pager_params
   * @param \Drupal\ol_main\Services\OlComments $comments
   * @param \Drupal\ol_members\Services\OlMembers $members
   * @param \Drupal\ol_main\Services\OlSections $sections
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
   * @param $gid
   *
   * @return array
   * @throws \Exception
   */
  public function getPostList($gid){

    // Pager init.
    $page = $this->pager_params->findPage();
    $num_per_page = 5;
    $offset = $num_per_page * $page;

    // Get posts data.
    $post_form = \Drupal::formBuilder()->getForm(\Drupal\ol_posts\Form\PostForm::class);
    $post_list_data = $this->posts->getPostsList(null, $num_per_page, $offset, null);
    $posts = $this->posts->renderPostsList($post_list_data);
    $page_title = $this->sections->getSectionOverrideTitle('posts', 'Posts');

    // Pager, now that we have the total number of results.
    $total_result = $this->posts->getPostsList(null, null, null, true);
    $pager = $this->pager->createPager($total_result, $num_per_page);
    $pager->getCurrentPage();

    // Build theme vars.
    $theme_vars = [
      'post_form' => $post_form,
      'posts' => $posts,
      'page_title' => $page_title,
    ];
    // Build render array.
    $render = [];
    $render[] = [
      '#theme' => 'posts_list',
      '#vars' => $theme_vars,
      '#type' => 'remote',
      '#attached' => [
        'library' => [
          'ol_posts/ol_posts'
        ],
      ],
    ];
    // Add pager and return.
    $render[] = ['#type' => 'pager'];
    return $render;
  }

  /**
   * @param $id
   *
   * @return array
   * @throws \Exception
   */
  public function getPost($id){
    // Get data.
    $post_data = $this->posts->getPostsList($id);
    $post_title = $this->posts->getPostTitle($post_data);
    $post = $this->posts->renderPostsList($post_data, 'page');
    $comment_form = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\CommentForm::class, null, null, 'post', $id);
    $comment_items = $this->comments->getComments($id, 'post', 'asc');
    $current_user_picture = $this->members->getUserPictureUrl(); // Should move to CommentForm

    // Build it.
    $theme_vars = [
      'post' => $post,
      'title' => $post_title,
      'comment_form' => $comment_form,
      'comment_items' => $comment_items,
      'current_user_picture' => $current_user_picture,
    ];
    $build = [
      '#theme' => 'post_page',
      '#vars' => $theme_vars,
      '#attached' => [
        'library' => [
          'ol_posts/ol_posts',
          'ol_main/ol_comments'
        ],
      ],
    ];
    return $build;
  }

}
