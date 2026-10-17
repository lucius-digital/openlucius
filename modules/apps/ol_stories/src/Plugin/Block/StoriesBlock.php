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
   * Stories service.
   *
   * @var \Drupal\ol_stories\Services\OlStories
   */
  protected $stories;

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
   * StoriesBlock factory method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   *   The created instance.
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
   * StoriesBlock constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\ol_stories\Services\OlStories $stories
   *   Stories service.
   * @param \Drupal\ol_main\Services\OlComments $comments
   *   Comments service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   Members service.
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
    if (!$user->hasPermission('access all openlucius content')) {
      return [];
    }

    // Get stories data.
    $story_form = \Drupal::formBuilder()->getForm('Drupal\ol_stories\Form\StoryForm');
    $story_list_data = $this->stories->getStoriesList();
    $stories_html = $this->stories->renderStoriesList($story_list_data);
    $current_user_picture = $this->members->getUserPictureUrl();
    $delete_story_form = \Drupal::formBuilder()->getForm('Drupal\ol_stories\Form\DeleteStoryForm');

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
          'ol_stories/ol_stories',
        ],
      ],
    ];
    return $render;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Work on caching.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
