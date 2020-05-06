<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ol_members\Services\OlMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SidebarUserBlock' block.
 *
 * @Block(
 *  id = "sidebar_user_block",
 *  admin_label = @Translation("Sidebar user block"),
 * )
 */
class SidebarUserBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var $account \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * @var $sections
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
      $container->get('current_user'),
      $container->get('olmembers.members')
    );
  }

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param AccountProxyInterface $account
   * @param $sections
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account, OlMembers $members) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->members = $members;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Prepare data.
    $username = $this->account->getAccountName();
    $uid = $this->account->id();
    $user_picture = $this->members->getUserPictureUrl($uid);

    // Build.
    $theme_vars = [
      'uid' => $uid,
      'username' => $username,
      'user_picture' => $user_picture,
    ];
    $build = [
      '#theme' => 'sidebar_user_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
    ];
    return $build;
  }
}
