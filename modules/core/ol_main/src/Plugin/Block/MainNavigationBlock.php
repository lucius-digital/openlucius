<?php

namespace Drupal\ol_main\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\ol_main\Services\OlGlobalConfig;
use Drupal\ol_main\Services\OlGroups;
use Drupal\ol_members\Services\OlMembers;
use Drupal\ol_search\Form\OlSearchNavForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MainNavigation' block.
 *
 * @Block(
 *  id = "main_navigation_block",
 *  admin_label = @Translation("Main navigation block"),
 * )
 */
class MainNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The OL members service.
   *
   * @var \Drupal\ol_members\Services\OlMembers
   */
  protected $members;

  /**
   * The OL groups service.
   *
   * @var \Drupal\ol_main\Services\OlGroups
   */
  protected $groups;

  /**
   * The global configuration service.
   *
   * @var \Drupal\ol_main\Services\OlGlobalConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $configuration,
          $plugin_id,
          $plugin_definition,
          $container->get('olmain.groups'),
          $container->get('current_user'),
          $container->get('olmembers.members'),
          $container->get('olmain.global_config')
      );
  }

  /**
   * Constructs a MainNavigationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   The OL groups service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   The OL members service.
   * @param \Drupal\ol_main\Services\OlGlobalConfig $config
   *   The global configuration service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OlGroups $groups, AccountProxyInterface $account, OlMembers $members, OlGlobalConfig $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groups = $groups;
    $this->account = $account;
    $this->members = $members;
    $this->config = $config;
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
    // Get search form, if module is installed.
    $search_form = '';
    if (\Drupal::moduleHandler()->moduleExists('ol_search')) {
      $search_form = \Drupal::formBuilder()->getForm(OlSearchNavForm::class);
    }
    // Get hooked menu items.
    $items = [];
    \Drupal::moduleHandler()->invokeAll('add_menu_top_right_links', [&$items]);
    // Get hooked user menu items.
    $user_menu_items = [];
    \Drupal::moduleHandler()->invokeAll('add_user_menu_links', [&$user_menu_items]);
    // Get hooked user menu items bottom.
    $user_menu_items_bottom = [];
    \Drupal::moduleHandler()->invokeAll('add_user_menu_links_bottom', [&$user_menu_items_bottom]);
    // Get color settings.
    $color_settings = $this->config->getColorSettings();
    // Get home header image.
    $image_url = $this->getHomeHeaderImage();

    // Build.
    $theme_vars = [
      'groups' => $groups,
      'uid' => $uid,
      'username' => $username,
      'user_picture' => $user_picture,
      'global_message' => $global_message,
      'menu_right_items' => $items,
      'user_menu_items' => $user_menu_items,
      'user_menu_items_bottom' => $user_menu_items_bottom,
      'search_form' => $search_form,
      'color_settings' => $color_settings,
      'home_header_image' => $image_url,
    ];
    return [
      '#theme' => 'main_nav_block',
      '#cache' => ['max-age' => 0],
      '#vars' => $theme_vars,
      '#attached' => [
        'library' => [
          'ol_main/ol_color_settings',
        ],
        'drupalSettings' => [
          'color_settings' => $color_settings,
        ],
      ],
    ];
  }

  /**
   * Get the home header image URL if the user has permission.
   */
  private function getHomeHeaderImage() {
    // Get home header image.
    $image_url = '';
    if ($this->account->hasPermission('access all openlucius content')) {
      $default_fid = $this->config->getHomeHeaderImage();
      if ($default_fid) {
        $file = File::load($default_fid);
        if ($file) {
          $image_url = ImageStyle::load('home_header_image')->buildUrl($file->getFileUri());
        }
      }
    }
    return $image_url;
  }

}
