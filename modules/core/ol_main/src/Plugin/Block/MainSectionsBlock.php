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
   * @param \Drupal\ol_main\Services\OlSections $sections
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   * @param \Drupal\ol_members\Services\OlMembers $members
   * @param \Drupal\ol_main\Services\OlGroups $groups
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
    if(!$gid) {
      return null;
    }
    $sections = $this->sections->getSectionsData();
    // Yeah.., this is not an ideal way to build url's, needs work.
    $themeable_sections = $this->makeSectionsThemeable($sections, $gid);

    // Nasty active styling for now.
    $path = \Drupal::request()->getpathInfo();
    $arg  = explode('/', $path);
    $active_section = ($arg[1] == 'group' && is_numeric($arg[2])) ? $arg[3] : null;

    // Build build.
    $theme_vars = [
      'sections' => $themeable_sections,
      'active_section' => $active_section,
      'gid' => $gid,
    ];
    $build = [
      '#theme' => 'main_sections_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }

  /**
   * TODO: Work on caching.
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * @param $sections
   * @param $current_gid
   *
   * @return array
   */
  private function makeSectionsThemeable($sections, $current_gid){
    // Get section override info and decode json to an array.
    $section_overrides_json = $this->sections->getSectionOverridesData($current_gid);
    $section_overrides = json_decode($section_overrides_json, true);
    // Build new, usable array.
    $options = array();
    foreach ($sections as $section){
      $path = (string) $section['path']; // Casting to string is needed here.
      $label = (string) $section['label']; // "
      $options[$path] = $label;
    }
    // Query sections that are enabled in this group.
    $enabled_sections = $this->sections->getEnabledSections($current_gid);
    // Filter enabled sections, so only installed ánd enabled sections will be shown.
    $enabled_sections = array_fill_keys($enabled_sections, null);
    $final_sections = array_intersect_key($options, $enabled_sections);
    // Create and return array that we can use in twig theme file.
    $themeable_sections = array();
    foreach ($final_sections as $key => $final_section){
      // Build path and label, including handling optional section name overrides.
      $override_value = (!empty($section_overrides[$key])) ? $section_overrides[$key] : $final_section;
      $themeable_sections[$final_section]['path'] = $key;
      $themeable_sections[$final_section]['badge_count'] = $this->getSectionCount($key);
      $themeable_sections[$final_section]['label'] = $override_value;
    }
    return $themeable_sections;
  }

  /**
   * Query counts for section badges.
   * todo: build dynamic via hook per module
   * @param $key
   *
   * @return |null
   */
  private function getSectionCount($key){

    // Init count.
    $count = null;
    // Check if an external modules must be involved to generate link.
    $count = \Drupal::moduleHandler()->invokeAll('sections_badges_count', [$key]);

    // Return if there is a $count invoked by hook.
    if(isset($count['count'])) {
      return $count['count'];
    }

    // If there was no count returned, than maybe it's a core one.
    switch ($key) {
      case 'messages':
        $table = 'ol_message';
        break;
      case 'posts':
        $table = 'ol_post';
        break;
      case 'members':
        $table = 'ol_group_user';
        break;
      case 'notebooks':
        $table = 'ol_text_doc';
        break;
    }
    // Fallback.
    if(empty($table)){
      return null;
    }

    // Count query.
    $gid = $this->route->getParameter('gid');
    $query = \Drupal::database()->select($table, 'oltable');
    $query->addField('oltable', 'id');
    $query->condition('oltable.group_id', $gid);
    $query->condition('oltable.status', 1);
    return $query->countQuery()->execute()->fetchField();
  }

}
