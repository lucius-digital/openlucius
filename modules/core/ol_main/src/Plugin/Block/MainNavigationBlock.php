<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MainNavigation' block.
 *
 * @Block(
 *  id = "main_navigation_block",
 *  admin_label = @Translation("Main navigation block"),
 * )
 */
class MainNavigationBlock extends BlockBase  implements ContainerFactoryPluginInterface{

  /**
   * @var $account
   */
  protected $account;

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
      $container->get('olmain.groups'),
      $container->get('current_user'),
      $container->get('olmembers.members')
    );
  }

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\ol_main\Services\OlGroups $groups
   * @param \Drupal\ol_main\Services\OlFiles $files
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OlGroups $groups,  AccountProxyInterface $account, OlMembers $members) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groups = $groups;
    $this->account = $account;
    $this->members = $members;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // User data.
    $username = $this->account->getAccountName();
    $uid = $this->account->id();
    $user_picture = $this->members->getUserPictureUrl($uid);
    // Groups data.
    $groups_data = $this->groups->getGroups(1);
    $groups = $this->groups->addActivityBadge($groups_data);
    // Global message.
    $global_message = \Drupal::config('ol_main.admin_settings')->get('global_message');

    // Build.
    $theme_vars = [
      'groups' => $groups,
      'uid' => $uid,
      'username' => $username,
      'user_picture' => $user_picture,
      'global_message' => $global_message,
    ];
    $build = [
      '#theme' => 'main_nav_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }



}
