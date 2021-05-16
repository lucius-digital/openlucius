<?php

namespace Drupal\ol_main\Services;


use Drupal\Core\Url;

/**
 * Class OlGlobalConfig.
 */
class OlGlobalConfig{

  /**
   * @var $current_user
   */
  protected $current_user;

  /**
   * @var $groups
   */
  protected $groups;

  /**
   * @var $members
   */
  protected $members;

  /**
   * OlMembers constructor.
   *
   * @param $current_user
   * @param $groups
   * @param $members
   */
  public function __construct($current_user, $groups, $members) {
    $this->current_user = $current_user;
    $this->groups = $groups;
    $this->members = $members;
  }

  /**
   * @return mixed
   */
  public function getDefaultColors(){
    $color_settings = new \StdClass();
    $color_settings->nav = '#0811fb';
    $color_settings->global_background = '#f1f3f6';
    return $color_settings;
  }

  /**
   * Returns fid of current group header image.
   *
   * @return integer
   */
  public function getHomeHeaderImage() {
    $gid = $this->groups->getGlobalGroupId();
    // Query if current user is group admin.
    $query = \Drupal::database()->select('ol_file', 'olf');
    $query->addField('olf', 'file_id');
    $query->condition('olf.group_id', $gid);
    $query->condition('olf.entity_type', 'home_header');
    return $query->execute()->fetchField();
  }


  /**
   * @return mixed
   */
  public function getConfigTabs(){
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
      'url' =>  Url::fromRoute('ol_main.general_settings_tabs')->toString(),
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
   * @param $ordered_items
   *
   * @return bool
   */
  public function updateHomeTabsPositions($ordered_items){
    // Encode for database.
    $json_items  = json_encode($ordered_items);
    // For security hardening.
    $group_id = $this->groups->getGlobalGroupId();
    $in_group = $this->members->checkUserInGroup($group_id);
    if($in_group && $group_id) {
      \Drupal::database()->update('ol_general_settings')
        ->fields([
          'tabs' => $json_items,
        ])
        ->condition('global_group_id', $group_id)
        ->execute();
      return true;
    }
    return false;
  }

  /**
   * @param $colors
   *
   * @return bool
   */
  public function updateColors($colors){
    // Encode for database.
    $json_colors  = json_encode($colors);
    // For security hardening.
    $group_id = $this->groups->getGlobalGroupId();
    $in_group = $this->members->checkUserInGroup($group_id);
    if($in_group && $group_id) {
      \Drupal::database()->update('ol_general_settings')
        ->fields([
          'colors' => $json_colors,
        ])
        ->condition('global_group_id', $group_id)
        ->execute();
      return true;
    }
    return false;
  }

  /**
   * @return mixed
   */
  public function getColorSettings(){
    // Get groups data.
    $query = \Drupal::database()->select('ol_general_settings', 'ogs');
    $query->addField('ogs', 'colors');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ogs.user_id');
    $query->addTag('ol_user_list');
    $color_config = json_decode($query->execute()->fetchField());
    if(empty($color_config)){
      $color_config = $this->getDefaultColors();
    }
    return $color_config;
  }


  /**
   * @return mixed
   */
  public function getHomeTabsPositions(){
    // Get groups data.
    $query = \Drupal::database()->select('ol_general_settings', 'ogs');
    $query->addField('ogs', 'tabs');
    $query->join('users_field_data', 'ufd', 'ufd.uid = ogs.user_id');
    $query->addTag('ol_user_list');
    return json_decode($query->execute()->fetchField());
  }

}
