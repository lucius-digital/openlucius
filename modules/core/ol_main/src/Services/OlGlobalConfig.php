<?php

namespace Drupal\ol_main\Services;

use Drupal\Core\Url;

/**
 * Service for managing global configuration settings.
 */
class OlGlobalConfig {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * Constructs an OlGlobalConfig instance.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   * @param \Drupal\ol_main\Services\OlGroups $groups
   *   The OL groups service.
   * @param \Drupal\ol_members\Services\OlMembers $members
   *   The OL members service.
   */
  public function __construct($current_user, $groups, $members) {
    $this->currentUser = $current_user;
    $this->groups = $groups;
    $this->members = $members;
  }

  /**
   * Get the default color settings.
   *
   * @return \stdClass
   *   Object containing default color values.
   */
  public function getDefaultColors() {
    $color_settings = new \StdClass();
    $color_settings->nav = '#00a573';
    $color_settings->global_background = '#f4f2ee';
    return $color_settings;
  }

  /**
   * Get the home header image file ID.
   *
   * @return int
   *   The file ID of the home header image.
   */
  public function getHomeHeaderImage() {
    $gid = $this->groups->getGlobalGroupId();
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('olf', 'file_id');
    $query->condition('olf.group_id', $gid);
    $query->condition('olf.entity_type', 'home_header');
    $query->condition('olf.status', 1);
    return $query->execute()->fetchField();
  }

  /**
   * Get configuration tabs for the settings page.
   *
   * @return array
   *   Array of tab definitions.
   */
  public function getConfigTabs() {
    // Config Tabs.
    $tabs['tabs'][] = [
      'label' => t('Color Settings'),
      'weight' => 20,
      'url' => Url::fromRoute('ol_main.general_settings_colors')->toString(),
      'icon' => 'lni lni-pallet',
    ];
    $tabs['tabs'][] = [
      'label' => t('Home Tabs'),
      'weight' => 10,
      'url' => Url::fromRoute('ol_main.general_settings_tabs')->toString(),
      'icon' => 'lni lni-tab',
    ];
    $tabs['tabs'][] = [
      'label' => t('Home Image'),
      'weight' => 30,
      'url' => Url::fromRoute('ol_main.home_header_image')->toString(),
      'icon' => 'lni lni-image',
    ];
    // Nasty active styling, to do later.
    $current_url = Url::fromRoute(\Drupal::routeMatch()->getRouteName())->toString();
    $tabs['current_url'] = $current_url;
    return $tabs;
  }

  /**
   * Update the home tabs positions.
   *
   * @param array $ordered_items
   *   Array of ordered tab items.
   *
   * @return bool
   *   TRUE if update was successful.
   */
  public function updateHomeTabsPositions($ordered_items) {
    // Encode for database.
    $json_items = json_encode($ordered_items);
    // For security hardening.
    $group_id = $this->groups->getGlobalGroupId();
    $in_group = $this->members->checkUserInGroup($group_id);
    if ($in_group && $group_id) {
      \Drupal::database()->update('ol_general_settings')
        ->fields(
                [
                  'tabs' => $json_items,
                ]
            )
        ->condition('global_group_id', $group_id)
        ->execute();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Update color settings.
   *
   * @param array $colors
   *   Array of color values.
   *
   * @return bool
   *   TRUE if update was successful.
   */
  public function updateColors($colors) {
    // Encode for database.
    $json_colors = json_encode($colors);
    // For security hardening.
    $group_id = $this->groups->getGlobalGroupId();
    $in_group = $this->members->checkUserInGroup($group_id);
    if ($in_group && $group_id) {
      \Drupal::database()->update('ol_general_settings')
        ->fields(
                [
                  'colors' => $json_colors,
                ]
            )
        ->condition('global_group_id', $group_id)
        ->execute();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the current color settings.
   *
   * @return \stdClass
   *   Object containing color settings.
   */
  public function getColorSettings() {
    // Get groups data.
    $query = \Drupal::database()->select('ol_general_settings', 'ogs');
    $query->addField('ogs', 'colors');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ogs.user_id');
    $query->addTag('ol_user_list');
    $result = $query->execute()->fetchField();
    $color_config = ($result) ? json_decode($result) : NULL;
    if (empty($color_config)) {
      $color_config = $this->getDefaultColors();
    }
    return $color_config;
  }

  /**
   * Get the home tabs positions.
   *
   * @return array
   *   Array of tab positions.
   */
  public function getHomeTabsPositions() {
    // Get groups data.
    $query = \Drupal::database()->select('ol_general_settings', 'ogs');
    $query->addField('ogs', 'tabs');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ogs.user_id');
    $query->addTag('ol_user_list');
    return json_decode($query->execute()->fetchField());
  }

}
