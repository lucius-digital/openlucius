<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ol_main\Services\OlFiles;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MainNavigation' block.
 *
 * @Block(
 *  id = "main_title_block",
 *  admin_label = @Translation("Main title block"),
 * )
 */
class MainTitleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The OL groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * The OL members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * The OL files service.
   *
   * @var \Drupal\ol_main\Services\OlFiles
   */
  protected $files;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $configuration,
          $plugin_id,
          $plugin_definition,
          $container->get('olmain.groups'),
          $container->get('olmembers.members'),
          $container->get('olmain.files')
      );
  }

  /**
   * Constructs a MainTitleBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   The OL groups service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   The OL members service.
   * @param \Drupal\ol_main\Services\OlFiles $files
   *   The OL files service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OlGroups $groups, OlMembers $members, OlFiles $files) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groups = $groups;
    $this->members = $members;
    $this->files = $files;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get data.
    $title_icon = NULL;
    $title = $this->groups->getGroupName();
    $group_id = $this->groups->getCurrentGroupId();
    $is_group_admin = $this->members->isGroupAdmin();
    $title_icon = ($title) ? 'users' : $this->getTitleIcon();

    // If there is no group active, get current page title and set.
    if (empty($title)) {
      $route = \Drupal::routeMatch()->getCurrentRouteMatch()->getRouteObject();
      $title = $route->getDefault('_title');
    }

    // Get group image.
    $group_image_url = NULL;
    if ($group_id) {
      $header_fid = $this->groups->getHeaderImage();
      if (!empty($header_fid)) {
        $header_uri = $this->files->getFileUri($header_fid);
        $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('50x50');
        $group_image_url = $style->buildUrl($header_uri);
      }
    }

    // Build.
    $theme_vars = [
      'title' => $title,
      'group_id' => $group_id,
      'is_group_admin' => $is_group_admin,
      'title_icon' => $title_icon,
      'group_image_url' => $group_image_url,
    ];
    $build = [
      '#theme' => 'main_title_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }

  /**
   * Determine the title icon based on the current route.
   */
  private function getTitleIcon() {
    // Homepage.
    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    if ($is_front) {
      $icon = 'home';
    }
    // @todo icons for all other routes.
    else {
      $icon = '';
    }
    return $icon;
  }

}
