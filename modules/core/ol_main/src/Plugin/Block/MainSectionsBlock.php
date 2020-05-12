<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_main\Services\OlSections;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;


/**
 * Provides a 'MainSections' block.
 *
 * @Block(
 *  id = "main_sections",
 *  admin_label = @Translation("Main Sections block"),
 * )
 */
class MainSectionsBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * @var $sections
   */
  protected $sections;

  /**
   * @var $sections
   */
  protected $route;

  /**
   * @var $sections
   */
  protected $members;

  /**
   * @var $groups
   */
  protected $groups;

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
      $container->get('olmain.sections'),
      $container->get('current_route_match'),
      $container->get('olmembers.members'),
      $container->get('olmain.groups')
    );
  }

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param $sections
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OlSections $sections, CurrentRouteMatch $route, OlMembers $members, OlGroups $groups) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sections = $sections;
    $this->route = $route;
    $this->members = $members;
    $this->groups = $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get all group sections.
    $gid = $this->route->getParameter('gid');
    $sections = $this->sections->getSectionsData();
    $is_group_admin = $this->members->isGroupAdmin();
    $group_name = $this->groups->getGroupName();
    // Yeah.., this is not a good way to build url's, needs work.
    $themeable_sections = $this->makeSectionsThemeable($sections, $gid);
    $host = \Drupal::request()->getHost();

    // Nasty active styling for now.
    $path = \Drupal::request()->getpathInfo();
    $arg  = explode('/', $path);
    $active_section = ($arg[1] == 'group' && is_numeric($arg[2])) ? $arg[3] : null;

    // Build build.
    $theme_vars = [
      'sections' => $themeable_sections,
      'active_section' => $active_section,
      'gid' => $gid,
      'is_group_admin' => $is_group_admin,
      'host' => $host,
      'group_name' => $group_name,
    ];
    $build = [
      '#theme' => 'main_sections_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }

  /**
   * @param $sections
   * @param $current_gid
   *
   * @return array
   */
  private function makeSectionsThemeable($sections, $current_gid){
    // Build new, usable array.
    $options = array();
    foreach ($sections as $section){
      $path = (string) $section['path']; // Casting to string is needed here.
      $options[$path] = $path;
    }
    // Query sections that are enabled in this group.
    $enabled_sections = $this->sections->getEnabledSections($current_gid);
    // Filter enabled sections, so only installed ánd enabled sections will be shown.
    $flat_sections = array_intersect($options, $enabled_sections);
    // Create and return array that we can use in twig theme file.
    $themeable_sections = array();
    foreach ($flat_sections as $flat_section){
      $themeable_sections[]['path'] = $flat_section;
    }
    return $themeable_sections;
  }
}
