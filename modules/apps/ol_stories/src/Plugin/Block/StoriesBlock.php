<?php

namespace Drupal\ol_stories\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ol_main\Services\OlComments;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_stories\Services\OlStories;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a 'Stories' block.
 *
 * @Block(
 *  id = "ol_stories",
 *  admin_label = @Translation("Stories block"),
 * )
 */
class StoriesBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * @var $comments
   */
  protected $stories;

  /**
   * @var $comments
   */
  protected $comments;

  /**
   * @var $members
   */
  protected $members;


  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('olstories.stories'),
      $container->get('olmain.comments'),
      $container->get('olmembers.members')
    );
  }

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\ol_stories\Services\OlStories $stories
   * @param \Drupal\ol_main\Services\OlComments $comments
   * @param \Drupal\ol_members\Services\OlMembers $members
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OlStories $stories, OlComments $comments, OlMembers $members) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stories = $stories;
    $this->comments = $comments;
    $this->members = $members;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Return empty if current user has no access to stories.
    $user = \Drupal::currentUser();
    if(!$user->hasPermission('access all openlucius content')) {
      return [];
    }

    // Get stories data.
    $story_form = \Drupal::formBuilder()->getForm(\Drupal\ol_stories\Form\StoryForm::class);
    $story_list_data = $this->stories->getStoriesList();
    $stories_html = $this->stories->renderStoriesList($story_list_data);
    $current_user_picture = $this->members->getUserPictureUrl();
    $delete_story_form = \Drupal::formBuilder()->getForm(\Drupal\ol_stories\Form\DeleteStoryForm::class);

    // Build theme vars.
    $theme_vars = [
      'story_form' => $story_form,
      'stories' => $stories_html,
      'current_user_picture' => $current_user_picture,
      'delete_story_form' => $delete_story_form,
    ];
    // Build render array.
    $render = [];
    $render[] = [
      '#theme' => 'stories_list',
      '#vars' => $theme_vars,
      '#type' => 'remote',
      '#attached' => [
        'library' => [
          'ol_stories/ol_stories'
        ],
      ],
    ];
    return $render;
  }

  /**
   * TODO: Work on caching.
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }


}
