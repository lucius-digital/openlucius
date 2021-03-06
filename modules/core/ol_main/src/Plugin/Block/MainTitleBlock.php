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
class MainTitleBlock extends BlockBase  implements ContainerFactoryPluginInterface{

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $members
   */
  protected $members;

  /**
   * @var $files
   */
  protected $files;

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
      $container->get('olmain.groups'),
      $container->get('olmembers.members'),
      $container->get('olmain.files')
    );
  }

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\ol_main\Services\OlGroups $groups
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OlGroups $groups, OlMembers $members, OlFiles $files ) {
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
    $title_icon = null;
    $title = $this->groups->getGroupName();
    $group_id = $this->groups->getCurrentGroupId();
    $is_group_admin = $this->members->isGroupAdmin();
    $title_icon = ($title) ? 'users': $this->getTitleIcon();

    // If there is no group active, get current page title and set
    if(empty($title)){
      $route = \Drupal::routeMatch()->getCurrentRouteMatch()->getRouteObject();
      $title = $route->getDefault('_title');
    }

    // Get group image.
    $group_image_url = null;
    if($group_id) {
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

  private function getTitleIcon(){
    // Homepage.
    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    if($is_front){
      $icon =  'home';
    }
    // Todo: icons for all other routes.
    else{
      $icon = '';
    }
    return $icon;
  }

}
