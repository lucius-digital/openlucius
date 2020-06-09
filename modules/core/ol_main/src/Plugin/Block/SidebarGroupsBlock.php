<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_main\Services\OlSections;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SidebarGroups' block.
 *
 * @Block(
 *  id = "sidebar_groups_block",
 *  admin_label = @Translation("Sidebar groups block"),
 * )
 */
class SidebarGroupsBlock extends BlockBase implements ContainerFactoryPluginInterface{

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
      $container->get('olmain.groups')
    );
  }


  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param $sections
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OlGroups $groups) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groups = $groups;
  }
  /**
   * {@inheritdoc}
   */
  public function build() {

    // Prepare data.
    $add_group_form = \Drupal::formBuilder()->getForm(\Drupal\ol_main\Form\AddGroupForm::class);
    $groups_data = $this->groups->getGroups();
    $groups = $this->groups->addActivityBadge($groups_data);
    $active_gid = $this->groups->getCurrentGroupId();

    // Build.
    $theme_vars = [
      'groups' => $groups,
      'active_gid' => $active_gid,
      'add_group_form' => $add_group_form,
    ];
    $build = [
      '#theme' => 'sidebar_groups_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }


}
