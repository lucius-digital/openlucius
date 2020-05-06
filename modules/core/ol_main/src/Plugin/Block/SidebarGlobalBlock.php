<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ol_main\Services\OlSections;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SidebarGlobal' block.
 *
 * @Block(
 *  id = "sidebar_global_block",
 *  admin_label = @Translation("Sidebar global block"),
 * )
 */
class SidebarGlobalBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var $account \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * @var $sections
   */
  protected $sections;

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
      $container->get('current_user'),
      $container->get('olmain.sections')
    );
  }

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param AccountProxyInterface $account
   * @param $sections
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account, OlSections $sections) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->sections = $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Prepare data.
    $menu_items = $this->sections->getGlobalMenuData();

    // Nasty active styling for now.
    $active_menu_item = $this->getActiveMenuItem();

    // Build.
    $theme_vars = [
      'menu_items' => $menu_items,
      'active_menu_item' => $active_menu_item,
    ];
    $build = [
      '#theme' => 'sidebar_global_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }

  /**
   * @return mixed
   */
  private function getActiveMenuItem(){
    $path = \Drupal::request()->getpathInfo();
    $arg  = explode('/', $path);
    $menu_item = ($arg[1] != 'group') ? $arg[1] : null;
    return $menu_item;
  }

}
